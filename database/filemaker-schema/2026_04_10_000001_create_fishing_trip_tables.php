<?php

return [
    'id' => '2026_04_10_000001_create_fishing_trip_tables',
    'description' => 'Create fishing trip tables and indexes',
    'operations' => [
        [
            'action' => 'create_table',
            'table' => 'fishing_trips',
            'fields' => [
                ['name' => 'id', 'type' => 'string', 'length' => 36, 'primary' => true],
                ['name' => 'user_id', 'type' => 'string', 'length' => 36],
                ['name' => 'trip_date', 'type' => 'date'],
                ['name' => 'start_at', 'type' => 'timestamp'],
                ['name' => 'end_at', 'type' => 'timestamp'],
                ['name' => 'river_name', 'type' => 'string', 'length' => 100],
                ['name' => 'point_name', 'type' => 'string', 'length' => 100],
                ['name' => 'tackle_name', 'type' => 'string', 'length' => 200],
                ['name' => 'memo', 'type' => 'text', 'length' => 2000],
                ['name' => 'created_at', 'type' => 'timestamp'],
                ['name' => 'updated_at', 'type' => 'timestamp'],
            ],
        ],
        [
            'action' => 'create_index',
            'table' => 'fishing_trips',
            'field' => 'user_id',
        ],
        [
            'action' => 'create_index',
            'table' => 'fishing_trips',
            'field' => 'trip_date',
        ],
        [
            'action' => 'create_table',
            'table' => 'fishing_trip_photos',
            'fields' => [
                ['name' => 'id', 'type' => 'string', 'length' => 36, 'primary' => true],
                ['name' => 'fishing_trip_id', 'type' => 'string', 'length' => 36],
                ['name' => 'image', 'type' => 'container'],
                ['name' => 'caption', 'type' => 'string', 'length' => 500],
                ['name' => 'sort_order', 'type' => 'int'],
                ['name' => 'created_at', 'type' => 'timestamp'],
                ['name' => 'updated_at', 'type' => 'timestamp'],
            ],
        ],
        [
            'action' => 'create_index',
            'table' => 'fishing_trip_photos',
            'field' => 'fishing_trip_id',
        ],
        [
            'action' => 'create_index',
            'table' => 'fishing_trip_photos',
            'field' => 'sort_order',
        ],
    ],
];
