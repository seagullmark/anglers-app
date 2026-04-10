<?php

namespace Tests\Unit\FileMakerSchema;

use App\FileMakerSchema\Drivers\FileMakerODataDriver;
use App\FileMakerSchema\ODataClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\TestCase;

class FileMakerODataDriverTest extends TestCase
{
    public function test_it_parses_table_and_field_metadata(): void
    {
        $client = $this->createMock(ODataClient::class);
        $client->expects($this->once())
            ->method('serviceDocument')
            ->willReturn([
                'value' => [
                    ['name' => 'fishing_trips', 'kind' => 'EntitySet', 'url' => 'fishing_trips'],
                ],
            ]);
        $client->expects($this->once())
            ->method('metadataXml')
            ->willReturn(<<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<edmx:Edmx Version="4.0" xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx">
  <edmx:DataServices>
    <Schema Namespace="com.filemaker.odata.anglers" xmlns="http://docs.oasis-open.org/odata/ns/edm">
      <EntityType Name="fishing_trips">
        <Key>
          <PropertyRef Name="trip_uuid"/>
        </Key>
        <Property Name="trip_uuid" Type="Edm.String" Nullable="false">
          <Annotation Term="Claris.Index" Bool="true"/>
        </Property>
        <Property Name="memo" Type="Edm.String" Nullable="true"/>
      </EntityType>
    </Schema>
  </edmx:DataServices>
</edmx:Edmx>
XML);

        $driver = new FileMakerODataDriver($client);

        $this->assertTrue($driver->tableExists('fishing_trips'));
        $this->assertTrue($driver->fieldExists('fishing_trips', 'memo'));
        $this->assertTrue($driver->fieldIsIndexed('fishing_trips', 'trip_uuid'));
        $this->assertFalse($driver->fieldIsIndexed('fishing_trips', 'memo'));
    }

    public function test_it_treats_duplicate_name_on_create_table_as_already_applied(): void
    {
        $client = $this->createMock(ODataClient::class);
        $client->expects($this->exactly(2))
            ->method('serviceDocument')
            ->willReturnOnConsecutiveCalls(
                [
                    'value' => [],
                ],
                [
                    'value' => [
                        ['name' => '_schema_migrations', 'kind' => 'EntitySet', 'url' => '_schema_migrations'],
                    ],
                ],
            );
        $client->expects($this->exactly(2))
            ->method('metadataXml')
            ->willReturnOnConsecutiveCalls(
                <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<edmx:Edmx Version="4.0" xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx">
  <edmx:DataServices>
    <Schema Namespace="com.filemaker.odata.anglers" xmlns="http://docs.oasis-open.org/odata/ns/edm">
    </Schema>
  </edmx:DataServices>
</edmx:Edmx>
XML,
                <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<edmx:Edmx Version="4.0" xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx">
  <edmx:DataServices>
    <Schema Namespace="com.filemaker.odata.anglers" xmlns="http://docs.oasis-open.org/odata/ns/edm">
      <EntityType Name="_schema_migrations">
        <Key>
          <PropertyRef Name="migration_id"/>
        </Key>
        <Property Name="migration_id" Type="Edm.String" Nullable="false"/>
      </EntityType>
    </Schema>
  </edmx:DataServices>
</edmx:Edmx>
XML
            );

        $client->expects($this->once())
            ->method('systemPath')
            ->with('FileMaker_Tables')
            ->willReturn('FileMaker_Tables');

        $client->expects($this->once())
            ->method('post')
            ->with('FileMaker_Tables', $this->anything())
            ->willThrowException(
                new RequestException(
                    new Response(new \GuzzleHttp\Psr7\Response(
                        400,
                        ['Content-Type' => 'application/json'],
                        '{"error": {"code": "12","message": "(12): Duplicate name"}}'
                    )),
                    new Request('POST', 'https://example.test/fmi/odata/v4/db/FileMaker_Tables')
                )
            );

        $driver = new FileMakerODataDriver($client);

        $driver->createTable('_schema_migrations', [
            ['name' => 'migration_id', 'type' => 'string', 'length' => 190, 'primary' => true],
        ]);

        $this->assertTrue($driver->tableExists('_schema_migrations'));
    }

    public function test_it_treats_duplicate_name_on_create_index_as_already_applied(): void
    {
        $client = $this->createMock(ODataClient::class);
        $client->expects($this->exactly(2))
            ->method('metadataXml')
            ->willReturn(<<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<edmx:Edmx Version="4.0" xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx">
  <edmx:DataServices>
    <Schema Namespace="com.filemaker.odata.anglers" xmlns="http://docs.oasis-open.org/odata/ns/edm">
      <EntityType Name="fishing_trips">
        <Key>
          <PropertyRef Name="trip_uuid"/>
        </Key>
        <Property Name="trip_uuid" Type="Edm.String" Nullable="false"/>
        <Property Name="owner_user_id" Type="Edm.String" Nullable="true"/>
      </EntityType>
    </Schema>
  </edmx:DataServices>
</edmx:Edmx>
XML);

        $client->expects($this->once())
            ->method('systemPath')
            ->with('FileMaker_Indexes', 'fishing_trips')
            ->willReturn('FileMaker_Indexes/fishing_trips');

        $client->expects($this->once())
            ->method('post')
            ->with('FileMaker_Indexes/fishing_trips', ['indexName' => 'owner_user_id'])
            ->willThrowException(
                new RequestException(
                    new Response(new \GuzzleHttp\Psr7\Response(
                        400,
                        ['Content-Type' => 'application/json'],
                        '{"error": {"code": "12","message": "(12): Duplicate name"}}'
                    )),
                    new Request('POST', 'https://example.test/fmi/odata/v4/db/FileMaker_Indexes/fishing_trips')
                )
            );

        $driver = new FileMakerODataDriver($client);

        $driver->createIndex('fishing_trips', 'owner_user_id');

        $this->assertTrue($driver->fieldExists('fishing_trips', 'owner_user_id'));
    }

    public function test_it_treats_duplicate_name_on_add_field_as_already_applied(): void
    {
        $client = $this->createMock(ODataClient::class);
        $client->expects($this->once())
            ->method('serviceDocument')
            ->willReturn([
                'value' => [
                    ['name' => '_schema_migrations', 'kind' => 'EntitySet', 'url' => '_schema_migrations'],
                ],
            ]);
        $client->expects($this->exactly(2))
            ->method('metadataXml')
            ->willReturnOnConsecutiveCalls(
                <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<edmx:Edmx Version="4.0" xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx">
  <edmx:DataServices>
    <Schema Namespace="com.filemaker.odata.anglers" xmlns="http://docs.oasis-open.org/odata/ns/edm">
      <EntityType Name="_schema_migrations">
        <Key>
          <PropertyRef Name="migration_id"/>
        </Key>
        <Property Name="migration_id" Type="Edm.String" Nullable="false"/>
      </EntityType>
    </Schema>
  </edmx:DataServices>
</edmx:Edmx>
XML,
                <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<edmx:Edmx Version="4.0" xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx">
  <edmx:DataServices>
    <Schema Namespace="com.filemaker.odata.anglers" xmlns="http://docs.oasis-open.org/odata/ns/edm">
      <EntityType Name="_schema_migrations">
        <Key>
          <PropertyRef Name="migration_id"/>
        </Key>
        <Property Name="migration_id" Type="Edm.String" Nullable="false"/>
        <Property Name="checksum" Type="Edm.String" Nullable="true"/>
      </EntityType>
    </Schema>
  </edmx:DataServices>
</edmx:Edmx>
XML
            );

        $client->expects($this->once())
            ->method('systemPath')
            ->with('FileMaker_Tables', '_schema_migrations')
            ->willReturn('FileMaker_Tables/_schema_migrations');

        $client->expects($this->once())
            ->method('patch')
            ->with('FileMaker_Tables/_schema_migrations', $this->anything())
            ->willThrowException(
                new RequestException(
                    new Response(new \GuzzleHttp\Psr7\Response(
                        400,
                        ['Content-Type' => 'application/json'],
                        '{"error": {"code": "12","message": "(12): Duplicate name"}}'
                    )),
                    new Request('PATCH', 'https://example.test/fmi/odata/v4/db/FileMaker_Tables/_schema_migrations')
                )
            );

        $driver = new FileMakerODataDriver($client);

        $driver->addFields('_schema_migrations', [
            ['name' => 'checksum', 'type' => 'string', 'length' => 64],
        ]);

        $this->assertTrue($driver->fieldExists('_schema_migrations', 'checksum'));
    }

    public function test_ensure_repository_table_does_not_mutate_existing_table(): void
    {
        $client = $this->createMock(ODataClient::class);
        $client->expects($this->once())
            ->method('serviceDocument')
            ->willReturn([
                'value' => [
                    ['name' => '_schema_migrations', 'kind' => 'EntitySet', 'url' => '_schema_migrations'],
                ],
            ]);
        $client->expects($this->never())->method('post');
        $client->expects($this->never())->method('patch');

        $driver = new FileMakerODataDriver($client);

        $driver->ensureRepositoryTable('_schema_migrations');

        $this->assertTrue($driver->tableExists('_schema_migrations'));
    }
}
