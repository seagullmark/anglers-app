<?php

namespace Tests\Unit\FileMakerSchema;

use App\FileMakerSchema\Contracts\SchemaDriver;
use App\FileMakerSchema\MigrationState;
use PHPUnit\Framework\TestCase;

class MigrationStateTest extends TestCase
{
    public function test_it_tracks_virtual_schema_changes_within_a_run(): void
    {
        $driver = $this->createMock(SchemaDriver::class);
        $driver->method('tableExists')->willReturn(false);
        $driver->method('fieldExists')->willReturn(false);
        $driver->method('fieldIsIndexed')->willReturn(false);

        $state = new MigrationState($driver);

        $state->markTableCreated('fishing_trips', [
            ['name' => 'trip_uuid', 'type' => 'string'],
            ['name' => 'owner_user_id', 'type' => 'string'],
        ]);

        $this->assertTrue($state->tableExists('fishing_trips'));
        $this->assertTrue($state->fieldExists('fishing_trips', 'owner_user_id'));

        $state->markIndexCreated('fishing_trips', 'owner_user_id');
        $this->assertTrue($state->fieldIsIndexed('fishing_trips', 'owner_user_id'));

        $state->markFieldDeleted('fishing_trips', 'owner_user_id');
        $this->assertFalse($state->fieldExists('fishing_trips', 'owner_user_id'));
        $this->assertFalse($state->fieldIsIndexed('fishing_trips', 'owner_user_id'));
    }
}
