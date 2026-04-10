<?php

namespace Tests\Unit\FileMakerSchema;

use App\FileMakerSchema\Contracts\SchemaDriver;
use App\FileMakerSchema\DefinitionLoader;
use App\FileMakerSchema\Exceptions\DestructiveOperationNotAllowedException;
use App\FileMakerSchema\MigrationRepository;
use App\FileMakerSchema\Migrator;
use PHPUnit\Framework\TestCase;

class MigratorTest extends TestCase
{
    public function test_it_applies_pending_migrations(): void
    {
        $definitions = [
            [
                'id' => '2026_04_10_000001_create_trips',
                'description' => 'Create fishing trips table',
                'checksum' => 'abc123',
                'path' => '/tmp/2026_04_10_000001_create_trips.php',
                'operations' => [
                    [
                        'action' => 'create_table',
                        'table' => 'fishing_trips',
                        'fields' => [
                            ['name' => 'trip_uuid', 'type' => 'string', 'length' => 36],
                        ],
                    ],
                    [
                        'action' => 'create_index',
                        'table' => 'fishing_trips',
                        'field' => 'trip_uuid',
                    ],
                ],
            ],
        ];

        $loader = $this->createMock(DefinitionLoader::class);
        $loader->expects($this->once())
            ->method('load')
            ->willReturn($definitions);

        $repository = $this->createMock(MigrationRepository::class);
        $repository->expects($this->once())
            ->method('ensureReady');
        $repository->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $repository->expects($this->once())
            ->method('all')
            ->willReturn([]);
        $repository->expects($this->once())
            ->method('nextBatchNumber')
            ->willReturn(4);
        $repository->expects($this->once())
            ->method('logMigration')
            ->with($definitions[0], 4);

        $driver = $this->createMock(SchemaDriver::class);
        $driver->expects($this->once())
            ->method('tableExists')
            ->with('fishing_trips')
            ->willReturn(false);
        $driver->expects($this->once())
            ->method('createTable')
            ->with('fishing_trips', $definitions[0]['operations'][0]['fields']);
        $driver->expects($this->never())
            ->method('fieldExists')
            ->with('fishing_trips', 'trip_uuid');
        $driver->expects($this->once())
            ->method('createIndex')
            ->with('fishing_trips', 'trip_uuid');

        $result = (new Migrator($loader, $repository, $driver))->apply();

        $this->assertFalse($result['pretend']);
        $this->assertSame(4, $result['batch']);
        $this->assertCount(1, $result['migrations']);
        $this->assertSame('2026_04_10_000001_create_trips', $result['migrations'][0]['id']);
    }

    public function test_change_field_requires_explicit_destructive_flag_before_dropping_old_field(): void
    {
        $definitions = [
            [
                'id' => '2026_04_10_000002_change_memo',
                'description' => 'Replace memo field',
                'checksum' => 'def456',
                'path' => '/tmp/2026_04_10_000002_change_memo.php',
                'operations' => [
                    [
                        'action' => 'change_field',
                        'table' => 'fishing_trips',
                        'field' => 'memo',
                        'target' => [
                            'name' => 'memo_v2',
                            'type' => 'text',
                            'length' => 4000,
                        ],
                        'drop_old' => true,
                    ],
                ],
            ],
        ];

        $loader = $this->createMock(DefinitionLoader::class);
        $loader->expects($this->once())
            ->method('load')
            ->willReturn($definitions);

        $repository = $this->createMock(MigrationRepository::class);
        $repository->expects($this->once())
            ->method('ensureReady');
        $repository->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $repository->expects($this->once())
            ->method('all')
            ->willReturn([]);
        $repository->expects($this->once())
            ->method('nextBatchNumber');
        $repository->expects($this->never())
            ->method('logMigration');

        $driver = $this->createMock(SchemaDriver::class);
        $driver->expects($this->never())
            ->method('fieldExists')
            ->with('fishing_trips', 'memo_v2');
        $driver->expects($this->never())
            ->method('addFields')
            ->with('fishing_trips', [$definitions[0]['operations'][0]['target']]);

        $migrator = new Migrator($loader, $repository, $driver);

        $this->expectException(DestructiveOperationNotAllowedException::class);

        $migrator->apply(false, false);
    }

    public function test_pretend_mode_does_not_try_to_prepare_repository_table(): void
    {
        $definitions = [
            [
                'id' => '2026_04_10_000001_create_trips',
                'description' => 'Create fishing trips table',
                'checksum' => 'abc123',
                'path' => '/tmp/2026_04_10_000001_create_trips.php',
                'operations' => [
                    [
                        'action' => 'create_table',
                        'table' => 'fishing_trips',
                        'fields' => [
                            ['name' => 'trip_uuid', 'type' => 'string', 'length' => 36],
                        ],
                    ],
                ],
            ],
        ];

        $loader = $this->createMock(DefinitionLoader::class);
        $loader->expects($this->once())
            ->method('load')
            ->willReturn($definitions);

        $repository = $this->createMock(MigrationRepository::class);
        $repository->expects($this->never())
            ->method('ensureReady');
        $repository->expects($this->once())
            ->method('exists')
            ->willReturn(false);
        $repository->expects($this->never())
            ->method('all');
        $repository->expects($this->never())
            ->method('nextBatchNumber');
        $repository->expects($this->never())
            ->method('logMigration');

        $driver = $this->createMock(SchemaDriver::class);
        $driver->expects($this->once())
            ->method('tableExists')
            ->with('fishing_trips')
            ->willReturn(false);
        $driver->expects($this->never())
            ->method('createTable');

        $result = (new Migrator($loader, $repository, $driver))->apply(true, false);

        $this->assertTrue($result['pretend']);
        $this->assertNull($result['batch']);
        $this->assertSame('2026_04_10_000001_create_trips', $result['migrations'][0]['id']);
    }
}
