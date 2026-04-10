<?php

namespace App\Console\Commands;

use App\FileMakerSchema\Migrator;
use Illuminate\Console\Command;
use Throwable;

class FmSchemaApplyCommand extends Command
{
    protected $signature = 'fm:schema:apply
        {--pretend : Show the operations without executing them}
        {--allow-destructive : Allow destructive operations such as deleting fields or indexes}';

    protected $description = 'Apply pending FileMaker OData schema migrations';

    public function handle(Migrator $migrator): int
    {
        $pretend = (bool) $this->option('pretend');
        $allowDestructive = (bool) $this->option('allow-destructive');

        try {
            $result = $migrator->apply($pretend, $allowDestructive);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($result['migrations'] === []) {
            $this->components->info('No pending FileMaker schema migrations.');

            return self::SUCCESS;
        }

        if ($pretend) {
            $this->components->info('Pretend mode: no FileMaker schema changes were executed.');
        }

        foreach ($result['migrations'] as $migration) {
            $headline = $migration['description'] !== ''
                ? "{$migration['id']} {$migration['description']}"
                : $migration['id'];

            $this->line($headline);

            foreach ($migration['steps'] as $step) {
                $this->line("  - {$step}");
            }
        }

        $this->newLine();

        if ($pretend) {
            $this->components->info(sprintf(
                'Prepared %d FileMaker schema migration(s).',
                count($result['migrations']),
            ));
        } else {
            $this->components->info(sprintf(
                'Applied %d FileMaker schema migration(s) in batch %d.',
                count($result['migrations']),
                $result['batch'],
            ));
        }

        return self::SUCCESS;
    }
}
