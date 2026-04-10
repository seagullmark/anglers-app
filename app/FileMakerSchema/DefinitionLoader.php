<?php

namespace App\FileMakerSchema;

use App\FileMakerSchema\Exceptions\InvalidDefinitionException;

class DefinitionLoader
{
    public function __construct(
        private readonly ?string $path = null,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function load(): array
    {
        $path = $this->path ?? config('filemaker_schema.definitions_path', database_path('filemaker-schema'));

        if (!is_dir($path)) {
            return [];
        }

        $files = glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);

        $definitions = [];

        foreach ($files as $file) {
            $definition = require $file;

            if (!is_array($definition)) {
                throw new InvalidDefinitionException("FileMaker schema definition [{$file}] must return an array.");
            }

            $this->validate($definition, $file);

            $definitions[] = [
                'id' => $definition['id'],
                'description' => $definition['description'] ?? '',
                'operations' => $definition['operations'],
                'checksum' => sha1_file($file),
                'path' => $file,
            ];
        }

        usort(
            $definitions,
            fn (array $left, array $right): int => strcmp((string) $left['id'], (string) $right['id'])
        );

        return $definitions;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function validate(array $definition, string $file): void
    {
        if (!is_string($definition['id'] ?? null) || $definition['id'] === '') {
            throw new InvalidDefinitionException("FileMaker schema definition [{$file}] must define a non-empty [id].");
        }

        if (!is_array($definition['operations'] ?? null) || $definition['operations'] === []) {
            throw new InvalidDefinitionException("FileMaker schema definition [{$file}] must define a non-empty [operations] array.");
        }

        foreach ($definition['operations'] as $index => $operation) {
            if (!is_array($operation)) {
                throw new InvalidDefinitionException("FileMaker schema definition [{$file}] operation #{$index} must be an array.");
            }

            if (!is_string($operation['action'] ?? null) || $operation['action'] === '') {
                throw new InvalidDefinitionException("FileMaker schema definition [{$file}] operation #{$index} must define a non-empty [action].");
            }
        }
    }
}
