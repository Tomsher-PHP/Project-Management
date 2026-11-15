<?php

$inboundModules = [
    'technologies' => require __DIR__.'/data_sync_explorer/inbound/technologies.php',
    'industries' => require __DIR__.'/data_sync_explorer/inbound/industries.php',
    'project_categories' => require __DIR__.'/data_sync_explorer/inbound/project_categories.php',
    'project_statuses' => require __DIR__.'/data_sync_explorer/inbound/project_statuses.php',
    'projects' => require __DIR__.'/data_sync_explorer/inbound/projects.php',
    'customers' => require __DIR__.'/data_sync_explorer/inbound/customers.php',
    'meetings' => require __DIR__.'/data_sync_explorer/inbound/meetings.php',
];

return [
    'metadata' => [
        'default_direction' => 'inbound',
        'default_module' => 'technologies',
        'example_directory' => 'data/data-sync-explorer/examples',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Directions
    |--------------------------------------------------------------------------
    |
    | Modules are registered explicitly so the complete configuration remains
    | deterministic and compatible with Laravel's configuration cache.
    |
    */
    'directions' => [
        'inbound' => [
            'label' => 'Inbound API',
            'enabled' => true,
            'module_order' => [
                'technologies',
                'industries',
                'project_categories',
                'project_statuses',
                'projects',
                'customers',
                'meetings',
            ],
            'modules' => $inboundModules,
        ],
        'outbound' => [
            'label' => 'Outbound API',
            'enabled' => false,
            'module_order' => [],
            'modules' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Explorer Categories
    |--------------------------------------------------------------------------
    */
    'categories' => [
        'settings' => [
            'name' => 'Settings',
            'description' => 'Core master lookup data identified by unique 6-digit numeric codes.',
        ],
        'operations' => [
            'name' => 'Operations',
            'description' => 'Business entities including Customers, Projects, and Meetings.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Operation Profiles
    |--------------------------------------------------------------------------
    */
    'header_profiles' => [
        'authorization_only' => [
            ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
        ],
        'json_read' => [
            ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
            ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
        ],
        'json_write' => [
            ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
            ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
            ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
        ],
        'json_write_without_accept' => [
            ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
            ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
        ],
    ],

    'error_profiles' => [
        'unauthenticated' => [
            401 => 'shared/unauthenticated.response-401.json',
        ],
        'authenticated' => [
            401 => 'shared/unauthenticated.response-401.json',
            403 => 'shared/insufficient-permissions.response-403.json',
        ],
        'record_not_found' => [
            404 => 'shared/record-not-found.response-404.json',
        ],
        'record_in_use' => [
            409 => 'shared/record-in-use.response-409.json',
        ],
        'validation_failed' => [
            422 => 'shared/validation-failed.response-422.json',
        ],
    ],
];
