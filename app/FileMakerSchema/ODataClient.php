<?php

namespace App\FileMakerSchema;

use App\FileMakerSchema\Exceptions\FileMakerSchemaException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class ODataClient
{
    /**
     * @param  array<string, mixed>|null  $config
     */
    public function __construct(
        private readonly Factory $http,
        private ?array $config = null,
    ) {
        $this->config ??= config('filemaker_schema', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->jsonResponse(
            $this->jsonRequest()->get($path, $query)
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload = []): array
    {
        return $this->jsonResponse(
            $this->jsonRequest()->post($path, $payload)
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function patch(string $path, array $payload = []): array
    {
        return $this->jsonResponse(
            $this->jsonRequest()->patch($path, $payload)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $path): array
    {
        return $this->jsonResponse(
            $this->jsonRequest()->delete($path)
        );
    }

    public function metadataXml(): string
    {
        return $this->xmlRequest()
            ->get('$metadata')
            ->throw()
            ->body();
    }

    /**
     * @return array<string, mixed>
     */
    public function serviceDocument(): array
    {
        return $this->jsonResponse(
            $this->jsonRequest()->get('')
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function records(string $table, array $query = []): array
    {
        $payload = $this->get($this->segment($table), $query);

        return is_array($payload['value'] ?? null) ? $payload['value'] : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createRecord(string $table, array $data): array
    {
        return $this->post($this->segment($table), $data);
    }

    public function systemPath(string ...$segments): string
    {
        return implode('/', array_map($this->segment(...), $segments));
    }

    private function jsonRequest(): PendingRequest
    {
        return $this->baseRequest()
            ->acceptJson()
            ->asJson();
    }

    private function xmlRequest(): PendingRequest
    {
        return $this->baseRequest()
            ->accept('application/xml');
    }

    private function baseRequest(): PendingRequest
    {
        $this->guardConfiguration();

        return $this->http
            ->baseUrl($this->baseUrl())
            ->timeout((int) ($this->config['timeout'] ?? 30))
            ->withBasicAuth(
                (string) $this->config['username'],
                (string) $this->config['password'],
            )
            ->withOptions([
                'verify' => (bool) ($this->config['verify_ssl'] ?? true),
            ]);
    }

    private function baseUrl(): string
    {
        return sprintf(
            '%s://%s/fmi/odata/%s/%s',
            $this->config['protocol'] ?? 'https',
            $this->config['host'] ?? '',
            $this->config['odata_version'] ?? 'v4',
            $this->config['database'] ?? '',
        );
    }

    private function guardConfiguration(): void
    {
        if (!($this->config['enabled'] ?? false)) {
            throw new FileMakerSchemaException('FileMaker schema access is disabled. Set FM_SCHEMA_ENABLED=true to run schema commands.');
        }

        foreach (['host', 'database', 'username', 'password'] as $key) {
            if (blank($this->config[$key] ?? null)) {
                throw new FileMakerSchemaException("Missing FileMaker schema configuration value [{$key}].");
            }
        }
    }

    /**
     * @param  string  $segment
     */
    private function segment(string $segment): string
    {
        if (str_starts_with($segment, '$')) {
            return $segment;
        }

        return rawurlencode($segment);
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonResponse(Response $response): array
    {
        $response->throw();

        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }
}
