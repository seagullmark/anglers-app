<?php

namespace App\FileMakerSchema;

use App\FileMakerSchema\Contracts\SchemaDriver;

class MigrationState
{
    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    private array $createdTables = [];

    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    private array $addedFields = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private array $deletedFields = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private array $createdIndexes = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private array $deletedIndexes = [];

    public function __construct(
        private readonly SchemaDriver $driver,
    ) {
    }

    public function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->createdTables)) {
            return true;
        }

        return $this->driver->tableExists($table);
    }

    public function fieldExists(string $table, string $field): bool
    {
        if (isset($this->deletedFields[$table][$field])) {
            return false;
        }

        if (isset($this->createdTables[$table][$field])) {
            return true;
        }

        if (isset($this->addedFields[$table][$field])) {
            return true;
        }

        return $this->driver->fieldExists($table, $field);
    }

    public function fieldIsIndexed(string $table, string $field): bool
    {
        if (isset($this->deletedIndexes[$table][$field])) {
            return false;
        }

        if (isset($this->createdIndexes[$table][$field])) {
            return true;
        }

        return $this->driver->fieldIsIndexed($table, $field);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    public function markTableCreated(string $table, array $fields): void
    {
        $this->createdTables[$table] = $this->fieldsToMap($fields);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    public function markFieldsAdded(string $table, array $fields): void
    {
        foreach ($this->fieldsToMap($fields) as $name => $definition) {
            $this->addedFields[$table][$name] = $definition;
            unset($this->deletedFields[$table][$name]);
        }
    }

    public function markFieldDeleted(string $table, string $field): void
    {
        $this->deletedFields[$table][$field] = true;
        unset($this->createdTables[$table][$field], $this->addedFields[$table][$field], $this->createdIndexes[$table][$field]);
    }

    public function markIndexCreated(string $table, string $field): void
    {
        $this->createdIndexes[$table][$field] = true;
        unset($this->deletedIndexes[$table][$field]);
    }

    public function markIndexDeleted(string $table, string $field): void
    {
        $this->deletedIndexes[$table][$field] = true;
        unset($this->createdIndexes[$table][$field]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, array<string, mixed>>
     */
    private function fieldsToMap(array $fields): array
    {
        $mapped = [];

        foreach ($fields as $field) {
            if (isset($field['name']) && is_string($field['name'])) {
                $mapped[$field['name']] = $field;
            }
        }

        return $mapped;
    }
}
