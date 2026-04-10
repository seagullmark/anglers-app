<?php

namespace App\FileMakerSchema;

use App\FileMakerSchema\Contracts\SchemaDriver;
use App\FileMakerSchema\Exceptions\DestructiveOperationNotAllowedException;
use App\FileMakerSchema\Exceptions\FileMakerSchemaException;
use App\FileMakerSchema\Exceptions\InvalidDefinitionException;
use Throwable;

class Migrator
{
    private ?MigrationState $state = null;

    public function __construct(
        private readonly DefinitionLoader $loader,
        private readonly MigrationRepository $repository,
        private readonly SchemaDriver $driver,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $definitions = $this->loader->load();
        $ran = $this->ranMap();
        $rows = [];
        $pending = [];

        foreach ($definitions as $definition) {
            $record = $ran[$definition['id']] ?? null;

            $row = [
                'id' => $definition['id'],
                'description' => $definition['description'],
                'status' => $record === null ? 'pending' : 'ran',
                'batch' => $record['batch_no'] ?? null,
                'path' => $definition['path'],
                'checksum' => $definition['checksum'],
            ];

            $rows[] = $row;

            if ($record === null) {
                $pending[] = $definition;
            }
        }

        return [
            'rows' => $rows,
            'pending' => $pending,
            'ran' => array_values($ran),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(bool $pretend = false, bool $allowDestructive = false): array
    {
        if (!$pretend) {
            $this->repository->ensureReady($this->driver);
        }

        $this->state = new MigrationState($this->driver);

        $pending = $this->pendingDefinitions();

        if ($pending === []) {
            return [
                'pretend' => $pretend,
                'batch' => null,
                'migrations' => [],
            ];
        }

        $batch = $pretend ? null : $this->repository->nextBatchNumber();
        $applied = [];

        foreach ($pending as $migration) {
            $steps = [];

            foreach ($migration['operations'] as $operation) {
                try {
                    $steps = [
                        ...$steps,
                        ...$this->applyOperation($operation, $pretend, $allowDestructive),
                    ];
                } catch (Throwable $e) {
                    $message = sprintf(
                        'Migration [%s] failed during [%s]: %s',
                        $migration['id'],
                        $this->describeOperation($operation),
                        $e->getMessage(),
                    );

                    if ($e instanceof DestructiveOperationNotAllowedException) {
                        throw new DestructiveOperationNotAllowedException($message, previous: $e);
                    }

                    if ($e instanceof InvalidDefinitionException) {
                        throw new InvalidDefinitionException($message, previous: $e);
                    }

                    if ($e instanceof FileMakerSchemaException) {
                        throw new FileMakerSchemaException($message, previous: $e);
                    }

                    throw new FileMakerSchemaException($message, previous: $e);
                }
            }

            if (!$pretend) {
                $this->repository->logMigration($migration, $batch);
            }

            $applied[] = [
                'id' => $migration['id'],
                'description' => $migration['description'],
                'steps' => $steps,
            ];
        }

        return [
            'pretend' => $pretend,
            'batch' => $batch,
            'migrations' => $applied,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function applyOperation(array $operation, bool $pretend, bool $allowDestructive): array
    {
        $action = $this->requireString($operation, 'action');

        return match ($action) {
            'create_table' => $this->applyCreateTable($operation, $pretend),
            'add_fields' => $this->applyAddFields($operation, $pretend),
            'delete_field' => $this->applyDeleteField($operation, $pretend, $allowDestructive),
            'create_index' => $this->applyCreateIndex($operation, $pretend),
            'delete_index' => $this->applyDeleteIndex($operation, $pretend, $allowDestructive),
            'change_field' => $this->applyChangeField($operation, $pretend, $allowDestructive),
            default => throw new InvalidDefinitionException("Unsupported FileMaker schema action [{$action}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<int, string>
     */
    private function applyCreateTable(array $operation, bool $pretend): array
    {
        $table = $this->requireString($operation, 'table');
        $fields = $this->requireFields($operation);

        if ($this->state()->tableExists($table)) {
            return ["skip create_table {$table} (already exists)"];
        }

        if (!$pretend) {
            $this->driver->createTable($table, $fields);
        }

        $this->state()->markTableCreated($table, $fields);

        return ["create_table {$table}"];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<int, string>
     */
    private function applyAddFields(array $operation, bool $pretend): array
    {
        $table = $this->requireString($operation, 'table');
        $fields = $this->requireFields($operation);

        if (!$this->state()->tableExists($table)) {
            throw new FileMakerSchemaException("Cannot add fields because table [{$table}] does not exist.");
        }

        $missingFields = array_values(array_filter(
            $fields,
            fn (array $field): bool => !$this->state()->fieldExists($table, (string) $field['name'])
        ));

        if ($missingFields === []) {
            return ["skip add_fields {$table} (all fields already exist)"];
        }

        if (!$pretend) {
            $this->driver->addFields($table, $missingFields);
        }

        $this->state()->markFieldsAdded($table, $missingFields);

        return [
            'add_fields ' . $table . ': ' . implode(', ', array_column($missingFields, 'name')),
        ];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<int, string>
     */
    private function applyDeleteField(array $operation, bool $pretend, bool $allowDestructive): array
    {
        $this->ensureDestructiveAllowed($allowDestructive, 'delete_field');

        $table = $this->requireString($operation, 'table');
        $field = $this->requireString($operation, 'field');

        if (!$this->state()->fieldExists($table, $field)) {
            return ["skip delete_field {$table}.{$field} (missing field)"];
        }

        if (!$pretend) {
            $this->driver->deleteField($table, $field);
        }

        $this->state()->markFieldDeleted($table, $field);

        return ["delete_field {$table}.{$field}"];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<int, string>
     */
    private function applyCreateIndex(array $operation, bool $pretend): array
    {
        $table = $this->requireString($operation, 'table');
        $field = $this->requireString($operation, 'field');

        if ($this->state()->fieldIsIndexed($table, $field)) {
            return ["skip create_index {$table}.{$field} (already indexed)"];
        }

        if (!$pretend) {
            $this->driver->createIndex($table, $field);
        }

        $this->state()->markIndexCreated($table, $field);

        return ["create_index {$table}.{$field}"];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<int, string>
     */
    private function applyDeleteIndex(array $operation, bool $pretend, bool $allowDestructive): array
    {
        $this->ensureDestructiveAllowed($allowDestructive, 'delete_index');

        $table = $this->requireString($operation, 'table');
        $field = $this->requireString($operation, 'field');

        if (!$this->state()->fieldExists($table, $field) || !$this->state()->fieldIsIndexed($table, $field)) {
            return ["skip delete_index {$table}.{$field} (index not present)"];
        }

        if (!$pretend) {
            $this->driver->deleteIndex($table, $field);
        }

        $this->state()->markIndexDeleted($table, $field);

        return ["delete_index {$table}.{$field}"];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<int, string>
     */
    private function applyChangeField(array $operation, bool $pretend, bool $allowDestructive): array
    {
        $table = $this->requireString($operation, 'table');
        $field = $this->requireString($operation, 'field');
        $strategy = $operation['strategy'] ?? 'replace';

        if ($strategy !== 'replace') {
            throw new InvalidDefinitionException("Unsupported change_field strategy [{$strategy}].");
        }

        if (($operation['copy_data'] ?? false) === true) {
            throw new FileMakerSchemaException('The [copy_data] option is not implemented yet. Use a separate backfill step.');
        }

        $dropOld = (bool) ($operation['drop_old'] ?? false);

        if ($dropOld) {
            $this->ensureDestructiveAllowed($allowDestructive, 'change_field.drop_old');
        }

        $target = $this->requireTargetField($operation);
        $messages = [];

        if (!$this->state()->fieldExists($table, $target['name'])) {
            if (!$pretend) {
                $this->driver->addFields($table, [$target]);
            }

            $this->state()->markFieldsAdded($table, [$target]);
            $messages[] = "change_field {$table}.{$field} -> add replacement {$target['name']}";
        } else {
            $messages[] = "skip change_field {$table}.{$field} replacement {$target['name']} (already exists)";
        }

        if ($dropOld) {
            if ($field !== $target['name'] && $this->state()->fieldExists($table, $field)) {
                if (!$pretend) {
                    $this->driver->deleteField($table, $field);
                }

                $this->state()->markFieldDeleted($table, $field);
                $messages[] = "change_field {$table}.{$field} -> drop old field";
            } else {
                $messages[] = "skip change_field {$table}.{$field} drop old field (not needed)";
            }
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<int, array<string, mixed>>
     */
    private function requireFields(array $operation): array
    {
        $fields = $operation['fields'] ?? null;

        if (!is_array($fields) || $fields === []) {
            throw new InvalidDefinitionException('FileMaker schema operation requires a non-empty [fields] array.');
        }

        foreach ($fields as $index => $field) {
            if (!is_array($field)) {
                throw new InvalidDefinitionException("FileMaker schema field definition #{$index} must be an array.");
            }

            $this->requireString($field, 'name');
            $this->requireString($field, 'type');
        }

        return array_values($fields);
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<string, mixed>
     */
    private function requireTargetField(array $operation): array
    {
        $target = $operation['target'] ?? null;

        if (!is_array($target)) {
            throw new InvalidDefinitionException('change_field requires a [target] field definition.');
        }

        $this->requireString($target, 'name');
        $this->requireString($target, 'type');

        return $target;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function requireString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;

        if (!is_string($value) || $value === '') {
            throw new InvalidDefinitionException("FileMaker schema definition requires a non-empty [{$key}] string.");
        }

        return $value;
    }

    private function ensureDestructiveAllowed(bool $allowDestructive, string $action): void
    {
        if (!$allowDestructive) {
            throw new DestructiveOperationNotAllowedException(
                "The [{$action}] operation is destructive. Re-run with --allow-destructive to continue."
            );
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function ranMap(): array
    {
        $records = [];

        if (!$this->repository->exists($this->driver)) {
            return $records;
        }

        foreach ($this->repository->all() as $record) {
            if (isset($record['migration_id'])) {
                $records[(string) $record['migration_id']] = $record;
            }
        }

        return $records;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pendingDefinitions(): array
    {
        $definitions = $this->loader->load();
        $ran = $this->ranMap();

        return array_values(array_filter(
            $definitions,
            fn (array $definition): bool => !array_key_exists((string) $definition['id'], $ran)
        ));
    }

    private function state(): MigrationState
    {
        return $this->state ??= new MigrationState($this->driver);
    }

    /**
     * @param  array<string, mixed>  $operation
     */
    private function describeOperation(array $operation): string
    {
        $action = (string) ($operation['action'] ?? 'unknown');
        $table = (string) ($operation['table'] ?? '');
        $field = (string) ($operation['field'] ?? '');

        return trim(implode(' ', array_filter([$action, $table !== '' ? "table={$table}" : null, $field !== '' ? "field={$field}" : null])));
    }
}
