<?php

namespace App\FileMakerSchema\Contracts;

interface SchemaDriver
{
    public function metadata(bool $refresh = false): array;

    public function tableExists(string $table, bool $refresh = false): bool;

    public function fieldExists(string $table, string $field, bool $refresh = false): bool;

    public function fieldIsIndexed(string $table, string $field, bool $refresh = false): bool;

    public function createTable(string $table, array $fields): void;

    public function addFields(string $table, array $fields): void;

    public function deleteField(string $table, string $field): void;

    public function createIndex(string $table, string $field): void;

    public function deleteIndex(string $table, string $field): void;

    public function ensureRepositoryTable(string $table): void;
}
