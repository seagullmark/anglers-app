<?php

namespace App\Console\Commands;

use App\FileMakerSchema\Migrator;
use Illuminate\Console\Command;
use Throwable;

class FmSchemaStatusCommand extends Command
{
    protected $signature = 'fm:schema:status';

    protected $description = 'Show FileMaker OData schema migration status';

    public function handle(Migrator $migrator): int
    {
        try {
            $status = $migrator->status();
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($status['rows'] === []) {
            $this->components->info('No FileMaker schema definitions were found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Status', 'Batch', 'Description'],
            array_map(
                fn (array $row): array => [
                    $row['id'],
                    $row['status'],
                    $row['batch'] ?? '-',
                    $row['description'] !== '' ? $row['description'] : '-',
                ],
                $status['rows'],
            ),
        );

        $this->newLine();
        $this->components->info(sprintf(
            'Pending: %d, Ran: %d',
            count($status['pending']),
            count($status['rows']) - count($status['pending']),
        ));

        return self::SUCCESS;
    }
}
