<?php

return [
    'enabled' => env('FM_SCHEMA_ENABLED', false),
    'protocol' => env('FM_SCHEMA_PROTOCOL', 'https'),
    'host' => env('FM_SCHEMA_HOST'),
    'database' => env('FM_SCHEMA_DATABASE'),
    'username' => env('FM_SCHEMA_USERNAME'),
    'password' => env('FM_SCHEMA_PASSWORD'),
    'odata_version' => env('FM_SCHEMA_ODATA_VERSION', 'v4'),
    'timeout' => (int) env('FM_SCHEMA_TIMEOUT', 30),
    'verify_ssl' => filter_var(env('FM_SCHEMA_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    'repository_table' => env('FM_SCHEMA_REPOSITORY_TABLE', '_schema_migrations'),
    'definitions_path' => database_path('filemaker-schema'),
];
