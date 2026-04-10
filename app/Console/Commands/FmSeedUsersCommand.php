<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\SampleUserFactory;
use Illuminate\Console\Command;
use Throwable;

class FmSeedUsersCommand extends Command
{
    protected $signature = 'fm:seed:users
        {--count=3 : Number of users to create}
        {--password=password : Shared plain-text password for all created users}
        {--email-prefix=sample-user : Email prefix}
        {--email-domain=example.test : Email domain}
        {--name-prefix=Sample User : Name prefix}';

    protected $description = 'Seed sample users into FileMaker';

    public function handle(SampleUserFactory $factory): int
    {
        $count = max(1, (int) $this->option('count'));
        $password = (string) $this->option('password');
        $emailPrefix = (string) $this->option('email-prefix');
        $emailDomain = (string) $this->option('email-domain');
        $namePrefix = (string) $this->option('name-prefix');

        $this->components->info(sprintf(
            'Creating %d sample FileMaker user(s). Shared password: %s',
            $count,
            $password,
        ));

        $created = [];

        for ($index = 1; $index <= $count; $index++) {
            $user = new User();

            foreach ($factory->makeUserAttributes(
                sequence: $index,
                password: $password,
                emailPrefix: $emailPrefix,
                emailDomain: $emailDomain,
                namePrefix: $namePrefix,
            ) as $key => $value) {
                $user->{$key} = $value;
            }

            try {
                $user->save();
            } catch (Throwable $e) {
                $this->components->error(sprintf(
                    'Failed to create sample user %d: %s',
                    $index,
                    $e->getMessage(),
                ));

                return self::FAILURE;
            }

            $created[] = [
                'id' => (string) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
            ];
        }

        $this->newLine();
        $this->table(['ID', 'Name', 'Email'], $created);
        $this->newLine();
        $this->components->info('Login password for all created users: ' . $password);

        return self::SUCCESS;
    }
}
