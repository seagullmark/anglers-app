<?php

namespace Tests\Unit\FileMakerSchema;

use App\FileMakerSchema\DefinitionLoader;
use PHPUnit\Framework\TestCase;

class DefinitionLoaderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/fm-schema-' . uniqid('', true);
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*.php') ?: [] as $file) {
            unlink($file);
        }

        @rmdir($this->directory);

        parent::tearDown();
    }

    public function test_it_loads_and_sorts_definition_files(): void
    {
        file_put_contents($this->directory . '/2026_04_10_000002_second.php', <<<'PHP'
<?php

return [
    'id' => '2026_04_10_000002_second',
    'description' => 'Second migration',
    'operations' => [
        ['action' => 'create_table', 'table' => 'beta', 'fields' => [['name' => 'id', 'type' => 'string']]],
    ],
];
PHP);

        file_put_contents($this->directory . '/2026_04_10_000001_first.php', <<<'PHP'
<?php

return [
    'id' => '2026_04_10_000001_first',
    'description' => 'First migration',
    'operations' => [
        ['action' => 'create_table', 'table' => 'alpha', 'fields' => [['name' => 'id', 'type' => 'string']]],
    ],
];
PHP);

        $definitions = (new DefinitionLoader($this->directory))->load();

        $this->assertCount(2, $definitions);
        $this->assertSame('2026_04_10_000001_first', $definitions[0]['id']);
        $this->assertSame('2026_04_10_000002_second', $definitions[1]['id']);
        $this->assertNotSame('', $definitions[0]['checksum']);
        $this->assertStringEndsWith('2026_04_10_000001_first.php', $definitions[0]['path']);
    }
}
