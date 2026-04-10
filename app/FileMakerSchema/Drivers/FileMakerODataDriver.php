<?php

namespace App\FileMakerSchema\Drivers;

use App\FileMakerSchema\Contracts\SchemaDriver;
use App\FileMakerSchema\Exceptions\FileMakerSchemaException;
use App\FileMakerSchema\ODataClient;
use Illuminate\Http\Client\RequestException;
use SimpleXMLElement;

class FileMakerODataDriver implements SchemaDriver
{
    /**
     * @var array<string, bool>|null
     */
    private ?array $tableNameCache = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $metadataCache = null;

    public function __construct(
        private readonly ODataClient $client,
    ) {
    }

    public function metadata(bool $refresh = false): array
    {
        if ($refresh || $this->metadataCache === null) {
            $this->metadataCache = $this->parseMetadata(
                $this->client->metadataXml()
            );
        }

        return $this->metadataCache;
    }

    public function tableExists(string $table, bool $refresh = false): bool
    {
        return array_key_exists($table, $this->tableNames($refresh));
    }

    public function fieldExists(string $table, string $field, bool $refresh = false): bool
    {
        return array_key_exists($field, $this->fieldsFor($table, $refresh));
    }

    public function fieldIsIndexed(string $table, string $field, bool $refresh = false): bool
    {
        return (bool) ($this->fieldsFor($table, $refresh)[$field]['indexed'] ?? false);
    }

    public function createTable(string $table, array $fields): void
    {
        if ($this->tableExists($table)) {
            return;
        }

        try {
            $this->client->post(
                $this->client->systemPath('FileMaker_Tables'),
                [
                    'tableName' => $table,
                    'fields' => $this->mapFields($fields),
                ],
            );
        } catch (RequestException $e) {
            $this->metadata(true);

            if ($this->isDuplicateNameError($e) && $this->tableExists($table, true)) {
                return;
            }

            throw $e;
        }

        $this->metadata(true);
    }

    public function addFields(string $table, array $fields): void
    {
        if (!$this->tableExists($table)) {
            throw new FileMakerSchemaException("FileMaker table [{$table}] does not exist.");
        }

        $missingFields = array_values(array_filter(
            $fields,
            fn (array $field): bool => !$this->fieldExists($table, (string) ($field['name'] ?? ''))
        ));

        if ($missingFields === []) {
            return;
        }

        $metadataRefreshed = false;

        foreach ($missingFields as $field) {
            try {
                $this->client->patch(
                    $this->client->systemPath('FileMaker_Tables', $table),
                    [
                        'fields' => [$this->mapField($field)],
                    ],
                );
            } catch (RequestException $e) {
                $this->metadata(true);
                $metadataRefreshed = true;

                if ($this->isDuplicateNameError($e) && $this->fieldExists($table, (string) $field['name'])) {
                    continue;
                }

                throw $e;
            }
        }

        if (!$metadataRefreshed) {
            $this->metadata(true);
        }
    }

    public function deleteField(string $table, string $field): void
    {
        if (!$this->fieldExists($table, $field)) {
            return;
        }

        if ((bool) ($this->fieldsFor($table)[$field]['primary'] ?? false)) {
            throw new FileMakerSchemaException("Cannot delete primary field [{$table}.{$field}].");
        }

        $this->client->delete(
            $this->client->systemPath('FileMaker_Tables', $table, $field)
        );

        $this->metadata(true);
    }

    public function createIndex(string $table, string $field): void
    {
        if ($this->fieldIsIndexed($table, $field)) {
            return;
        }

        try {
            $this->client->post(
                $this->client->systemPath('FileMaker_Indexes', $table),
                [
                    'indexName' => $field,
                ],
            );
        } catch (RequestException $e) {
            $this->metadata(true);

            if ($this->isDuplicateNameError($e)) {
                return;
            }

            throw $e;
        }

        $this->metadata(true);
    }

    public function deleteIndex(string $table, string $field): void
    {
        if (!$this->fieldExists($table, $field) || !$this->fieldIsIndexed($table, $field)) {
            return;
        }

        $this->client->delete(
            $this->client->systemPath('FileMaker_Indexes', $table, $field)
        );

        $this->metadata(true);
    }

    public function ensureRepositoryTable(string $table): void
    {
        if (!$this->tableExists($table, true)) {
            $this->createTable($table, [
                ['name' => 'migration_id', 'type' => 'string', 'length' => 190, 'primary' => true],
                ['name' => 'checksum', 'type' => 'string', 'length' => 64],
                ['name' => 'batch_no', 'type' => 'int'],
                ['name' => 'applied_at', 'type' => 'timestamp'],
            ]);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function tables(bool $refresh = false): array
    {
        return $this->metadata($refresh)['tables'] ?? [];
    }

    /**
     * @return array<string, bool>
     */
    private function tableNames(bool $refresh = false): array
    {
        if ($refresh || $this->tableNameCache === null) {
            $document = $this->client->serviceDocument();
            $value = $document['value'] ?? [];
            $names = [];

            if (is_array($value)) {
                foreach ($value as $item) {
                    if (is_array($item) && is_string($item['name'] ?? null) && $item['name'] !== '') {
                        $names[$item['name']] = true;
                    }
                }
            }

            // Fallback to metadata parsing if the service document is empty.
            if ($names === []) {
                foreach (array_keys($this->tables($refresh)) as $tableName) {
                    $names[$tableName] = true;
                }
            }

            $this->tableNameCache = $names;
        }

        return $this->tableNameCache;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fieldsFor(string $table, bool $refresh = false): array
    {
        return $this->tables($refresh)[$table]['fields'] ?? [];
    }

    /**
     * @param  string  $xml
     * @return array<string, mixed>
     */
    private function parseMetadata(string $xml): array
    {
        $document = simplexml_load_string($xml);

        if (!$document instanceof SimpleXMLElement) {
            throw new FileMakerSchemaException('Unable to parse FileMaker OData metadata.');
        }

        $edmNamespace = 'http://docs.oasis-open.org/odata/ns/edm';

        $document->registerXPathNamespace('edm', $edmNamespace);

        $entityTypes = $document->xpath('//edm:EntityType') ?: [];
        $tables = [];

        foreach ($entityTypes as $entityType) {
            if (!$entityType instanceof SimpleXMLElement) {
                continue;
            }

            $entityType->registerXPathNamespace('edm', $edmNamespace);

            $tableName = (string) $entityType['Name'];

            if ($tableName === '') {
                continue;
            }

            $keys = [];

            foreach ($entityType->xpath('./edm:Key/edm:PropertyRef') ?: [] as $propertyRef) {
                if ($propertyRef instanceof SimpleXMLElement) {
                    $keys[] = (string) $propertyRef['Name'];
                }
            }

            $fields = [];

            foreach ($entityType->xpath('./edm:Property') ?: [] as $property) {
                if (!$property instanceof SimpleXMLElement) {
                    continue;
                }

                $fieldName = (string) $property['Name'];

                if ($fieldName === '') {
                    continue;
                }

                $annotations = $this->extractAnnotations($property, $edmNamespace);

                $fields[$fieldName] = [
                    'name' => $fieldName,
                    'type' => (string) $property['Type'],
                    'nullable' => (string) $property['Nullable'] !== 'false',
                    'primary' => in_array($fieldName, $keys, true),
                    'indexed' => $this->annotationBool($annotations, 'Index'),
                    'annotations' => $annotations,
                ];
            }

            $tables[$tableName] = [
                'name' => $tableName,
                'primary_key' => $keys,
                'fields' => $fields,
            ];
        }

        ksort($tables);

        return ['tables' => $tables];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractAnnotations(SimpleXMLElement $property, string $namespace): array
    {
        $annotations = [];

        $property->registerXPathNamespace('edm', $namespace);

        foreach ($property->xpath('./edm:Annotation') ?: [] as $annotation) {
            if (!$annotation instanceof SimpleXMLElement) {
                continue;
            }

            $term = (string) $annotation['Term'];
            $key = str_contains($term, '.') ? (string) str($term)->afterLast('.') : $term;

            $value = (string) ($annotation['Bool'] ?: $annotation['String'] ?: $annotation['Int'] ?: $annotation['Path']);

            $annotations[$key] = $value === '' ? true : $value;
        }

        return $annotations;
    }

    /**
     * @param  array<string, mixed>  $annotations
     */
    private function annotationBool(array $annotations, string $key): bool
    {
        return filter_var($annotations[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function mapFields(array $fields): array
    {
        return array_map(fn (array $field): array => $this->mapField($field), $fields);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function mapField(array $field): array
    {
        if (!is_string($field['name'] ?? null) || $field['name'] === '') {
            throw new FileMakerSchemaException('Each FileMaker schema field must define a non-empty [name].');
        }

        if (!is_string($field['type'] ?? null) || $field['type'] === '') {
            throw new FileMakerSchemaException("FileMaker schema field [{$field['name']}] must define a non-empty [type].");
        }

        $mapped = [
            'name' => $field['name'],
            'type' => $this->normalizeType($field),
        ];

        foreach (['primary', 'unique', 'nullable', 'default'] as $key) {
            if (array_key_exists($key, $field)) {
                $mapped[$key] = $field[$key];
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function normalizeType(array $field): string
    {
        $type = strtolower((string) $field['type']);

        if (preg_match('/^(varchar|numeric|int|integer|date|time|timestamp|blob)\b/i', $type) === 1) {
            return strtoupper($field['type']);
        }

        return match ($type) {
            'string' => sprintf('VARCHAR(%d)', (int) ($field['length'] ?? 255)),
            'text' => sprintf('VARCHAR(%d)', (int) ($field['length'] ?? 2000)),
            'int', 'integer' => 'INT',
            'numeric' => 'NUMERIC',
            'date' => 'DATE',
            'time' => 'TIME',
            'timestamp' => 'TIMESTAMP',
            'container', 'blob' => 'BLOB',
            default => throw new FileMakerSchemaException("Unsupported FileMaker schema type [{$field['type']}]."),
        };
    }

    private function isDuplicateNameError(RequestException $exception): bool
    {
        $response = $exception->response;

        if ($response === null || $response->status() !== 400) {
            return false;
        }

        $body = $response->body();

        return str_contains($body, '"code": "12"')
            || str_contains($body, '"code":"12"')
            || str_contains(strtolower($body), 'duplicate name');
    }
}
