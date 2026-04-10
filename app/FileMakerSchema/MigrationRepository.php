<?php

namespace App\FileMakerSchema;

use App\FileMakerSchema\Contracts\SchemaDriver;

class MigrationRepository
{
    public function __construct(
        private readonly ODataClient $client,
        private readonly ?string $table = null,
    ) {
    }

    public function ensureReady(SchemaDriver $driver): void
    {
        try {
            $driver->ensureRepositoryTable($this->tableName());
        } catch (\Throwable $e) {
            throw new \App\FileMakerSchema\Exceptions\FileMakerSchemaException(
                "Failed to prepare FileMaker schema repository table [{$this->tableName()}]: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    public function exists(SchemaDriver $driver): bool
    {
        return $driver->tableExists($this->tableName(), true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $records = $this->client->records($this->tableName(), [
            '$orderby' => 'migration_id',
        ]);

        usort(
            $records,
            fn (array $left, array $right): int => strcmp((string) ($left['migration_id'] ?? ''), (string) ($right['migration_id'] ?? ''))
        );

        return $records;
    }

    public function nextBatchNumber(): int
    {
        $batches = array_map(
            fn (array $record): int => (int) ($record['batch_no'] ?? 0),
            $this->all(),
        );

        return $batches === [] ? 1 : max($batches) + 1;
    }

    /**
     * @param  array<string, mixed>  $migration
     */
    public function logMigration(array $migration, int $batch): void
    {
        $this->client->createRecord($this->tableName(), [
            'migration_id' => $migration['id'],
            'checksum' => $migration['checksum'],
            'batch_no' => $batch,
            'applied_at' => now()->toDateTimeString(),
        ]);
    }

    private function tableName(): string
    {
        return $this->table ?? config('filemaker_schema.repository_table', '_schema_migrations');
    }
}
