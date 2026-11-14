<?php

use App\Models\ApiClient;

return [
    /*
    |--------------------------------------------------------------------------
    | Data Sync Explorer Categories
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
    | Data Sync Explorer Modules & Operations
    |--------------------------------------------------------------------------
    */
    'modules' => [
        // =====================================================================
        // 1. TECHNOLOGIES
        // =====================================================================
        'technologies' => [
            'category' => 'settings',
            'name' => 'Technologies',
            'slug' => 'technologies',
            'description' => 'Manage core technology stack masters used across PMS projects.',
            'operations' => [
                'list' => [
                    'id' => 'technologies.list',
                    'name' => 'List Technologies',
                    'description' => 'Retrieve a paginated list of technology records with optional search, sorting, and active status filters.',
                    'method' => 'GET',
                    'endpoint' => '/api/data-sync/settings/technologies',
                    'scope' => ApiClient::SCOPE_SETTINGS_READ,
                    'scope_description' => 'Allows reading settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                    ],
                    'path_params' => [],
                    'query_params' => [
                        ['name' => 'page', 'type' => 'integer', 'required' => false, 'default' => '1', 'description' => 'Page number (>= 1).'],
                        ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'default' => '50', 'description' => 'Records per page (1 to 100).'],
                        ['name' => 'search', 'type' => 'string', 'required' => false, 'default' => 'null', 'description' => 'Search term matching name or code.'],
                        ['name' => 'is_active', 'type' => 'boolean', 'required' => false, 'default' => 'null', 'description' => 'Filter by active status (1 or 0).'],
                        ['name' => 'sort_by', 'type' => 'string', 'required' => false, 'default' => 'sort_order', 'description' => 'id, name, code, sort_order, is_active, created_at.'],
                        ['name' => 'sort_direction', 'type' => 'string', 'required' => false, 'default' => 'asc', 'description' => 'asc or desc.'],
                    ],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Records retrieved successfully.',
                            'data' => [
                                [
                                    'id' => 1,
                                    'name' => 'Laravel Framework',
                                    'code' => '500001',
                                    'sort_order' => 1,
                                    'is_system' => true,
                                    'is_active' => true,
                                ],
                                [
                                    'id' => 2,
                                    'name' => 'React.js',
                                    'code' => '500002',
                                    'sort_order' => 2,
                                    'is_system' => false,
                                    'is_active' => true,
                                ],
                            ],
                            'pagination' => [
                                'current_page' => 1,
                                'per_page' => 50,
                                'total' => 2,
                                'last_page' => 1,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'Insufficient API permissions.'],
                    ],
                ],
                'show' => [
                    'id' => 'technologies.show',
                    'name' => 'Get Technology',
                    'description' => 'Retrieve a single technology record by its unique 6-digit numeric code.',
                    'method' => 'GET',
                    'endpoint' => '/api/data-sync/settings/technologies/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_READ,
                    'scope_description' => 'Allows reading settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '500001', 'description' => 'Unique 6-digit code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record retrieved successfully.',
                            'data' => [
                                'id' => 1,
                                'name' => 'Laravel Framework',
                                'code' => '500001',
                                'sort_order' => 1,
                                'is_system' => true,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'Insufficient API permissions.'],
                        404 => ['success' => false, 'message' => 'Record not found.'],
                    ],
                ],
                'create' => [
                    'id' => 'technologies.create',
                    'name' => 'Create Technology',
                    'description' => 'Create a new technology master record. The 6-digit code will be auto-generated if omitted.',
                    'method' => 'POST',
                    'endpoint' => '/api/data-sync/settings/technologies',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                        ['name' => 'Idempotency-Key', 'value' => 'unique-req-uuid-or-hash', 'required' => false, 'note' => 'Optional UUID or hash to prevent duplicate creation.'],
                    ],
                    'path_params' => [],
                    'query_params' => [],
                    'request_payload' => [
                        'name' => 'TypeScript',
                        'code' => '500010',
                        'sort_order' => 5,
                        'is_active' => true,
                    ],
                    'response_success' => [
                        'status' => 201,
                        'body' => [
                            'success' => true,
                            'message' => 'Record created successfully.',
                            'data' => [
                                'id' => 15,
                                'name' => 'TypeScript',
                                'code' => '500010',
                                'sort_order' => 5,
                                'is_system' => false,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'Insufficient API permissions.'],
                        409 => ['success' => false, 'message' => 'A record with this code already exists.'],
                        422 => ['success' => false, 'message' => 'Validation failed.', 'errors' => ['name' => ['The name has already been taken.']]],
                    ],
                ],
                'update' => [
                    'id' => 'technologies.update',
                    'name' => 'Update Technology',
                    'description' => 'Update an existing technology record identified by its 6-digit code. The code cannot be modified.',
                    'method' => 'PUT',
                    'endpoint' => '/api/data-sync/settings/technologies/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '500010', 'description' => 'Code of the record to update.'],
                    ],
                    'query_params' => [],
                    'request_payload' => [
                        'name' => 'TypeScript 5.x',
                        'sort_order' => 6,
                    ],
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record updated successfully.',
                            'data' => [
                                'id' => 15,
                                'name' => 'TypeScript 5.x',
                                'code' => '500010',
                                'sort_order' => 6,
                                'is_system' => false,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'Insufficient API permissions.'],
                        404 => ['success' => false, 'message' => 'Record not found.'],
                        422 => ['success' => false, 'message' => 'Validation failed.'],
                    ],
                ],
                'toggle_status' => [
                    'id' => 'technologies.toggle_status',
                    'name' => 'Toggle Status',
                    'description' => 'Activate or deactivate a technology record via partial PUT without altering other fields.',
                    'method' => 'PUT',
                    'endpoint' => '/api/data-sync/settings/technologies/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '500010', 'description' => 'Code of the record.'],
                    ],
                    'query_params' => [],
                    'request_payload' => [
                        'is_active' => false,
                    ],
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record updated successfully.',
                            'data' => [
                                'id' => 15,
                                'name' => 'TypeScript 5.x',
                                'code' => '500010',
                                'sort_order' => 6,
                                'is_system' => false,
                                'is_active' => false,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'Insufficient API permissions.'],
                        404 => ['success' => false, 'message' => 'Record not found.'],
                    ],
                ],
                'delete' => [
                    'id' => 'technologies.delete',
                    'name' => 'Delete Technology',
                    'description' => 'Soft delete a technology record. Protected system records and records currently attached to projects cannot be deleted.',
                    'method' => 'DELETE',
                    'endpoint' => '/api/data-sync/settings/technologies/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '500010', 'description' => 'Code of the record to delete.'],
                    ],
                    'query_params' => [],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record deleted successfully.',
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'System records cannot be deleted.'],
                        404 => ['success' => false, 'message' => 'Record not found.'],
                        409 => ['success' => false, 'message' => 'This record is currently in use and cannot be deleted.'],
                    ],
                ],
            ],
        ],

        // =====================================================================
        // 2. INDUSTRIES
        // =====================================================================
        'industries' => [
            'category' => 'settings',
            'name' => 'Industries',
            'slug' => 'industries',
            'description' => 'Manage hierarchical industry sectors for customer categorization.',
            'operations' => [
                'list' => [
                    'id' => 'industries.list',
                    'name' => 'List Industries',
                    'description' => 'Retrieve a paginated list of industries with resolved parent industry codes.',
                    'method' => 'GET',
                    'endpoint' => '/api/data-sync/settings/industries',
                    'scope' => ApiClient::SCOPE_SETTINGS_READ,
                    'scope_description' => 'Allows reading settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                    ],
                    'path_params' => [],
                    'query_params' => [
                        ['name' => 'page', 'type' => 'integer', 'required' => false, 'default' => '1', 'description' => 'Page number.'],
                        ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'default' => '50', 'description' => 'Records per page.'],
                        ['name' => 'search', 'type' => 'string', 'required' => false, 'default' => 'null', 'description' => 'Search name or code.'],
                    ],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Records retrieved successfully.',
                            'data' => [
                                [
                                    'id' => 1,
                                    'name' => 'Information Technology',
                                    'code' => '743813',
                                    'parent_code' => null,
                                    'sort_order' => 1,
                                    'is_system' => true,
                                    'is_active' => true,
                                ],
                            ],
                            'pagination' => ['current_page' => 1, 'per_page' => 50, 'total' => 1, 'last_page' => 1],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'Insufficient API permissions.'],
                    ],
                ],
                'show' => [
                    'id' => 'industries.show',
                    'name' => 'Get Industry',
                    'description' => 'Retrieve an industry by 6-digit code.',
                    'method' => 'GET',
                    'endpoint' => '/api/data-sync/settings/industries/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_READ,
                    'scope_description' => 'Allows reading settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '743813', 'description' => 'Industry code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record retrieved successfully.',
                            'data' => [
                                'id' => 1,
                                'name' => 'Information Technology',
                                'code' => '743813',
                                'parent_code' => null,
                                'sort_order' => 1,
                                'is_system' => true,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        404 => ['success' => false, 'message' => 'Record not found.'],
                    ],
                ],
                'create' => [
                    'id' => 'industries.create',
                    'name' => 'Create Industry',
                    'description' => 'Create an industry with an optional parent_code.',
                    'method' => 'POST',
                    'endpoint' => '/api/data-sync/settings/industries',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                        ['name' => 'Idempotency-Key', 'value' => 'unique-req-uuid-or-hash', 'required' => false, 'note' => 'Optional UUID.'],
                    ],
                    'path_params' => [],
                    'query_params' => [],
                    'request_payload' => [
                        'name' => 'Artificial Intelligence',
                        'code' => '743820',
                        'parent_code' => '743813',
                        'sort_order' => 10,
                        'is_active' => true,
                    ],
                    'response_success' => [
                        'status' => 201,
                        'body' => [
                            'success' => true,
                            'message' => 'Record created successfully.',
                            'data' => [
                                'id' => 12,
                                'name' => 'Artificial Intelligence',
                                'code' => '743820',
                                'parent_code' => '743813',
                                'sort_order' => 10,
                                'is_system' => false,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        422 => ['success' => false, 'message' => 'Validation failed.', 'errors' => ['parent_code' => ['The selected parent industry code is invalid.']]],
                    ],
                ],
                'update' => [
                    'id' => 'industries.update',
                    'name' => 'Update Industry',
                    'description' => 'Update industry details or re-parent under another industry.',
                    'method' => 'PUT',
                    'endpoint' => '/api/data-sync/settings/industries/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '743820', 'description' => 'Industry code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => [
                        'name' => 'Applied Artificial Intelligence',
                        'sort_order' => 12,
                    ],
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record updated successfully.',
                            'data' => [
                                'id' => 12,
                                'name' => 'Applied Artificial Intelligence',
                                'code' => '743820',
                                'parent_code' => '743813',
                                'sort_order' => 12,
                                'is_system' => false,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        404 => ['success' => false, 'message' => 'Record not found.'],
                        422 => ['success' => false, 'message' => 'Circular parent industry relationship is not allowed.'],
                    ],
                ],
                'toggle_status' => [
                    'id' => 'industries.toggle_status',
                    'name' => 'Toggle Status',
                    'description' => 'Activate or deactivate an industry record via partial PUT without altering other fields.',
                    'method' => 'PUT',
                    'endpoint' => '/api/data-sync/settings/industries/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '743820', 'description' => 'Industry code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => [
                        'is_active' => false,
                    ],
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record updated successfully.',
                            'data' => [
                                'id' => 12,
                                'name' => 'Applied Artificial Intelligence',
                                'code' => '743820',
                                'parent_code' => '743813',
                                'sort_order' => 12,
                                'is_system' => false,
                                'is_active' => false,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'Insufficient API permissions.'],
                        404 => ['success' => false, 'message' => 'Record not found.'],
                    ],
                ],
                'delete' => [
                    'id' => 'industries.delete',
                    'name' => 'Delete Industry',
                    'description' => 'Soft delete an industry. Rejected if child industries or customers exist.',
                    'method' => 'DELETE',
                    'endpoint' => '/api/data-sync/settings/industries/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '743820', 'description' => 'Industry code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => ['success' => true, 'message' => 'Record deleted successfully.'],
                    ],
                    'response_errors' => [
                        409 => ['success' => false, 'message' => 'This record is currently in use and cannot be deleted.'],
                    ],
                ],
            ],
        ],

        // =====================================================================
        // 3. PROJECT CATEGORIES
        // =====================================================================
        'project_categories' => [
            'category' => 'settings',
            'name' => 'Project Categories',
            'slug' => 'project_categories',
            'description' => 'Manage project categorization masters (e.g., Web Development, Mobile Apps).',
            'operations' => [
                'list' => [
                    'id' => 'project_categories.list',
                    'name' => 'List Project Categories',
                    'description' => 'Retrieve a paginated list of project categories.',
                    'method' => 'GET',
                    'endpoint' => '/api/data-sync/settings/project_categories',
                    'scope' => ApiClient::SCOPE_SETTINGS_READ,
                    'scope_description' => 'Allows reading settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                    ],
                    'path_params' => [],
                    'query_params' => [
                        ['name' => 'page', 'type' => 'integer', 'required' => false, 'default' => '1', 'description' => 'Page number.'],
                        ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'default' => '50', 'description' => 'Records per page.'],
                    ],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Records retrieved successfully.',
                            'data' => [
                                [
                                    'id' => 1,
                                    'name' => 'Web Application Development',
                                    'code' => '600001',
                                    'sort_order' => 1,
                                    'is_system' => true,
                                    'is_active' => true,
                                ],
                            ],
                            'pagination' => ['current_page' => 1, 'per_page' => 50, 'total' => 1, 'last_page' => 1],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                    ],
                ],
                'show' => [
                    'id' => 'project_categories.show',
                    'name' => 'Get Project Category',
                    'description' => 'Retrieve category details by 6-digit code.',
                    'method' => 'GET',
                    'endpoint' => '/api/data-sync/settings/project_categories/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_READ,
                    'scope_description' => 'Allows reading settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '600001', 'description' => 'Category code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record retrieved successfully.',
                            'data' => [
                                'id' => 1,
                                'name' => 'Web Application Development',
                                'code' => '600001',
                                'sort_order' => 1,
                                'is_system' => true,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        404 => ['success' => false, 'message' => 'Record not found.'],
                    ],
                ],
                'create' => [
                    'id' => 'project_categories.create',
                    'name' => 'Create Project Category',
                    'description' => 'Create a project category.',
                    'method' => 'POST',
                    'endpoint' => '/api/data-sync/settings/project_categories',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [],
                    'query_params' => [],
                    'request_payload' => [
                        'name' => 'Cloud Migration & DevOps',
                        'code' => '600005',
                        'sort_order' => 5,
                        'is_active' => true,
                    ],
                    'response_success' => [
                        'status' => 201,
                        'body' => [
                            'success' => true,
                            'message' => 'Record created successfully.',
                            'data' => [
                                'id' => 8,
                                'name' => 'Cloud Migration & DevOps',
                                'code' => '600005',
                                'sort_order' => 5,
                                'is_system' => false,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        422 => ['success' => false, 'message' => 'Validation failed.'],
                    ],
                ],
                'update' => [
                    'id' => 'project_categories.update',
                    'name' => 'Update Project Category',
                    'description' => 'Update category name or sort order.',
                    'method' => 'PUT',
                    'endpoint' => '/api/data-sync/settings/project_categories/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '600005', 'description' => 'Category code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => [
                        'name' => 'Cloud Architecture & DevOps',
                    ],
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record updated successfully.',
                            'data' => [
                                'id' => 8,
                                'name' => 'Cloud Architecture & DevOps',
                                'code' => '600005',
                                'sort_order' => 5,
                                'is_system' => false,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        404 => ['success' => false, 'message' => 'Record not found.'],
                    ],
                ],
                'toggle_status' => [
                    'id' => 'project_categories.toggle_status',
                    'name' => 'Toggle Status',
                    'description' => 'Activate or deactivate a project category record via partial PUT without altering other fields.',
                    'method' => 'PUT',
                    'endpoint' => '/api/data-sync/settings/project_categories/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '600005', 'description' => 'Category code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => [
                        'is_active' => false,
                    ],
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record updated successfully.',
                            'data' => [
                                'id' => 8,
                                'name' => 'Cloud Architecture & DevOps',
                                'code' => '600005',
                                'sort_order' => 5,
                                'is_system' => false,
                                'is_active' => false,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'Insufficient API permissions.'],
                        404 => ['success' => false, 'message' => 'Record not found.'],
                    ],
                ],
                'delete' => [
                    'id' => 'project_categories.delete',
                    'name' => 'Delete Project Category',
                    'description' => 'Delete a project category. Blocked if attached to any projects.',
                    'method' => 'DELETE',
                    'endpoint' => '/api/data-sync/settings/project_categories/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '600005', 'description' => 'Category code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => ['success' => true, 'message' => 'Record deleted successfully.'],
                    ],
                    'response_errors' => [
                        409 => ['success' => false, 'message' => 'This record is currently in use and cannot be deleted.'],
                    ],
                ],
            ],
        ],

        // =====================================================================
        // 4. PROJECT STATUSES
        // =====================================================================
        'project_statuses' => [
            'category' => 'settings',
            'name' => 'Project Statuses',
            'slug' => 'project_statuses',
            'description' => 'Manage project lifecycle stages and status codes.',
            'operations' => [
                'list' => [
                    'id' => 'project_statuses.list',
                    'name' => 'List Project Statuses',
                    'description' => 'Retrieve project status records including color, status type, and default flag.',
                    'method' => 'GET',
                    'endpoint' => '/api/data-sync/settings/project_statuses',
                    'scope' => ApiClient::SCOPE_SETTINGS_READ,
                    'scope_description' => 'Allows reading settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                    ],
                    'path_params' => [],
                    'query_params' => [
                        ['name' => 'page', 'type' => 'integer', 'required' => false, 'default' => '1', 'description' => 'Page number.'],
                    ],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Records retrieved successfully.',
                            'data' => [
                                [
                                    'id' => 1,
                                    'name' => 'In Progress',
                                    'code' => '100001',
                                    'color' => '#0d6efd',
                                    'type' => 'in_progress',
                                    'is_default' => false,
                                    'is_completed' => false,
                                    'sort_order' => 1,
                                    'is_system' => true,
                                    'is_active' => true,
                                ],
                            ],
                            'pagination' => ['current_page' => 1, 'per_page' => 50, 'total' => 1, 'last_page' => 1],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                    ],
                ],
                'show' => [
                    'id' => 'project_statuses.show',
                    'name' => 'Get Project Status',
                    'description' => 'Retrieve a single project status by code.',
                    'method' => 'GET',
                    'endpoint' => '/api/data-sync/settings/project_statuses/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_READ,
                    'scope_description' => 'Allows reading settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '100001', 'description' => 'Status code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record retrieved successfully.',
                            'data' => [
                                'id' => 1,
                                'name' => 'In Progress',
                                'code' => '100001',
                                'color' => '#0d6efd',
                                'type' => 'in_progress',
                                'is_default' => false,
                                'is_completed' => false,
                                'sort_order' => 1,
                                'is_system' => true,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        404 => ['success' => false, 'message' => 'Record not found.'],
                    ],
                ],
                'create' => [
                    'id' => 'project_statuses.create',
                    'name' => 'Create Project Status',
                    'description' => 'Create a project status with specified type and hex color.',
                    'method' => 'POST',
                    'endpoint' => '/api/data-sync/settings/project_statuses',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [],
                    'query_params' => [],
                    'request_payload' => [
                        'name' => 'Under Quality Review',
                        'code' => '100020',
                        'color' => '#f59e0b',
                        'type' => 'in_progress',
                        'is_default' => false,
                        'sort_order' => 15,
                        'is_active' => true,
                    ],
                    'response_success' => [
                        'status' => 201,
                        'body' => [
                            'success' => true,
                            'message' => 'Record created successfully.',
                            'data' => [
                                'id' => 14,
                                'name' => 'Under Quality Review',
                                'code' => '100020',
                                'color' => '#f59e0b',
                                'type' => 'in_progress',
                                'is_default' => false,
                                'is_completed' => false,
                                'sort_order' => 15,
                                'is_system' => false,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        422 => ['success' => false, 'message' => 'Validation failed.'],
                    ],
                ],
                'update' => [
                    'id' => 'project_statuses.update',
                    'name' => 'Update Project Status',
                    'description' => 'Update name, color, type or default setting of a status.',
                    'method' => 'PUT',
                    'endpoint' => '/api/data-sync/settings/project_statuses/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '100020', 'description' => 'Status code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => [
                        'name' => 'QA in Progress',
                        'color' => '#eab308',
                    ],
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record updated successfully.',
                            'data' => [
                                'id' => 14,
                                'name' => 'QA in Progress',
                                'code' => '100020',
                                'color' => '#eab308',
                                'type' => 'in_progress',
                                'is_default' => false,
                                'is_completed' => false,
                                'sort_order' => 15,
                                'is_system' => false,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        404 => ['success' => false, 'message' => 'Record not found.'],
                    ],
                ],
                'toggle_status' => [
                    'id' => 'project_statuses.toggle_status',
                    'name' => 'Toggle Status',
                    'description' => 'Activate or deactivate a project status record via partial PUT without altering other fields.',
                    'method' => 'PUT',
                    'endpoint' => '/api/data-sync/settings/project_statuses/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '100020', 'description' => 'Status code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => [
                        'is_active' => false,
                    ],
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Record updated successfully.',
                            'data' => [
                                'id' => 14,
                                'name' => 'QA in Progress',
                                'code' => '100020',
                                'color' => '#eab308',
                                'type' => 'in_progress',
                                'is_default' => false,
                                'is_completed' => false,
                                'sort_order' => 15,
                                'is_system' => false,
                                'is_active' => false,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'Insufficient API permissions.'],
                        404 => ['success' => false, 'message' => 'Record not found.'],
                    ],
                ],
                'delete' => [
                    'id' => 'project_statuses.delete',
                    'name' => 'Delete Project Status',
                    'description' => 'Delete project status. Default status and statuses assigned to projects cannot be deleted.',
                    'method' => 'DELETE',
                    'endpoint' => '/api/data-sync/settings/project_statuses/{code}',
                    'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
                    'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                    ],
                    'path_params' => [
                        ['name' => 'code', 'type' => 'string (6 digits)', 'required' => true, 'example' => '100020', 'description' => 'Status code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => ['success' => true, 'message' => 'Record deleted successfully.'],
                    ],
                    'response_errors' => [
                        409 => ['success' => false, 'message' => 'This record is currently in use and cannot be deleted.'],
                    ],
                ],
            ],
        ],

        // =====================================================================
        // 5. PROJECTS
        // =====================================================================
        'projects' => [
            'category' => 'operations',
            'name' => 'Projects',
            'slug' => 'projects',
            'description' => 'Inbound creation and update of Projects and linked Customers.',
            'operations' => [
                'create' => [
                    'id' => 'projects.create',
                    'name' => 'Create Customer & Projects',
                    'description' => 'Atomically synchronizes one customer organization, customer contacts, and one or multiple projects (up to 50 projects per batch). Creates permanent external identifier mappings to prevent duplicate records.',
                    'method' => 'POST',
                    'endpoint' => '/api/data-sync/projects',
                    'scope' => ApiClient::SCOPE_PROJECTS_WRITE,
                    'scope_description' => 'Allows creating and updating Customer and Project records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                        ['name' => 'Idempotency-Key', 'value' => 'crm-sync-batch-20261113-001', 'required' => false, 'note' => 'Optional UUID or batch token to cache and replay identical response for 10 minutes.'],
                    ],
                    'path_params' => [],
                    'query_params' => [],
                    'request_payload' => [
                        'customer' => [
                            'external_code' => 'CRM-CUS-1025',
                            'external_id' => '9021',
                            'name' => 'ABC Technologies LLC',
                            'email' => 'info@abctech.ae',
                            'industry_code' => '743813',
                            'website' => 'https://abctech.ae',
                            'country_code' => 'AE',
                            'emirate' => 'Dubai',
                            'company_address' => 'Level 14, Al Saada Tower, Dubai',
                            'sales_person_email' => 'sales@abctech.ae',
                            'contacts' => [
                                [
                                    'name' => 'Ahmed Mohammed',
                                    'email' => 'ahmed@abctech.ae',
                                    'mobile' => '+971501234567',
                                    'designation' => 'Director',
                                    'is_primary' => true,
                                ],
                                [
                                    'name' => 'Sarah Jenkins',
                                    'email' => 'sarah@abctech.ae',
                                    'mobile' => '+971507654321',
                                    'designation' => 'Technical Lead',
                                    'is_primary' => false,
                                ],
                            ],
                        ],
                        'projects' => [
                            [
                                'external_code' => 'CRM-PRJ-1001',
                                'external_id' => 'PRJ-901',
                                'name' => 'Corporate Portal Redesign',
                                'project_flow' => 'agile',
                                'priority' => 'high',
                                'status_code' => '100001',
                                'stage_code' => '200001',
                                'start_date' => '2026-12-01',
                                'end_date' => '2027-02-28',
                                'customer_end_date' => '2027-03-15',
                                'estimated_time_minutes' => 12000,
                                'customer_estimate_minutes' => 10800,
                                'default_task_estimate_seconds' => 3600,
                                'domain' => 'portal.abctech.ae',
                                'sales_person_email' => 'sales@abctech.ae',
                                'technology_codes' => ['500001', '500002'],
                                'category_codes' => ['600001'],
                                'service_type_codes' => ['700001'],
                                'default_billable' => true,
                                'is_active' => true,
                            ],
                            [
                                'external_code' => 'CRM-PRJ-1002',
                                'external_id' => 'PRJ-902',
                                'name' => 'Mobile Companion Application',
                                'project_flow' => 'agile',
                                'priority' => 'medium',
                                'status_code' => '100001',
                                'stage_code' => '200001',
                                'start_date' => '2027-01-15',
                                'end_date' => '2027-04-30',
                                'estimated_time_minutes' => 9000,
                                'sales_person_email' => 'sales@abctech.ae',
                                'technology_codes' => ['500002'],
                                'category_codes' => ['600002'],
                                'default_billable' => true,
                                'is_active' => true,
                            ],
                        ],
                    ],
                    'response_success' => [
                        'status' => 201,
                        'body' => [
                            'success' => true,
                            'message' => 'Customer and projects processed successfully.',
                            'data' => [
                                'customer' => [
                                    'customer_id' => 25,
                                    'customer_code' => 'CUS00025',
                                    'created' => true,
                                ],
                                'projects' => [
                                    [
                                        'project_id' => 101,
                                        'project_code' => 'PRJ00101',
                                        'external_code' => 'CRM-PRJ-1001',
                                        'external_id' => 'PRJ-901',
                                        'created' => true,
                                    ],
                                    [
                                        'project_id' => 102,
                                        'project_code' => 'PRJ00102',
                                        'external_code' => 'CRM-PRJ-1002',
                                        'external_id' => 'PRJ-902',
                                        'created' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'Insufficient API permissions.'],
                        409 => ['success' => false, 'message' => 'Conflicting external customer identifiers.'],
                        422 => ['success' => false, 'message' => 'Validation failed.'],
                    ],
                ],
                'update' => [
                    'id' => 'projects.update',
                    'name' => 'Update Project',
                    'description' => 'Performs partial updates on an existing project identified by its project_code. Omitted fields retain existing values.',
                    'method' => 'PUT',
                    'endpoint' => '/api/integrations/projects/{project_code}',
                    'scope' => ApiClient::SCOPE_PROJECTS_WRITE,
                    'scope_description' => 'Allows creating and updating Customer and Project records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [
                        ['name' => 'project_code', 'type' => 'string', 'required' => true, 'example' => 'PRJ00101', 'description' => 'Unique PMS project code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => [
                        'name' => 'Corporate Portal Redesign - Phase 2',
                        'priority' => 'urgent',
                        'status_code' => '100002',
                        'stage_code' => '200002',
                        'end_date' => '2027-03-31',
                        'estimated_time_minutes' => 15000,
                        'technology_codes' => ['500001', '500002'],
                        'category_codes' => ['600001'],
                    ],
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Project updated successfully.',
                            'data' => [
                                'project_id' => 101,
                                'project_code' => 'PRJ00101',
                                'updated' => true,
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        404 => ['success' => false, 'message' => 'Project not found.'],
                        409 => ['success' => false, 'message' => 'Conflicting external project identifiers.'],
                        422 => ['success' => false, 'message' => 'Validation failed.'],
                    ],
                ],
            ],
        ],

        // =====================================================================
        // 6. CUSTOMERS
        // =====================================================================
        'customers' => [
            'category' => 'operations',
            'name' => 'Customers',
            'slug' => 'customers',
            'description' => 'Inbound update of customer organizations and contacts.',
            'operations' => [
                'update' => [
                    'id' => 'customers.update',
                    'name' => 'Update Customer',
                    'description' => 'Update customer organization attributes and contacts. Supplying contact id updates the existing contact, while omitting id creates a new contact.',
                    'method' => 'PUT',
                    'endpoint' => '/api/integrations/customers/{customer_code}',
                    'scope' => ApiClient::SCOPE_PROJECTS_WRITE,
                    'scope_description' => 'Allows creating and updating Customer and Project records.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                        ['name' => 'Content-Type', 'value' => 'application/json', 'required' => true, 'note' => 'JSON payload format.'],
                    ],
                    'path_params' => [
                        ['name' => 'customer_code', 'type' => 'string', 'required' => true, 'example' => 'CUS00025', 'description' => 'Unique PMS customer code.'],
                    ],
                    'query_params' => [],
                    'request_payload' => [
                        'name' => 'ABC Technologies International LLC',
                        'email' => 'contact@abctech.ae',
                        'industry_code' => '743813',
                        'country_code' => 'AE',
                        'emirate' => 'Dubai',
                        'website' => 'https://abctech.ae',
                        'company_address' => 'Office 402, Business Bay, Dubai',
                        'sales_person_email' => 'sales@abctech.ae',
                        'is_active' => true,
                        'contacts' => [
                            [
                                'id' => 15,
                                'name' => 'Ahmed Mohammed',
                                'designation' => 'Managing Director',
                                'mobile' => '+971501234567',
                                'is_primary' => true,
                            ],
                            [
                                'name' => 'Fatima Al-Zahra',
                                'email' => 'fatima@abctech.ae',
                                'mobile' => '+971509988776',
                                'designation' => 'Operations Lead',
                                'is_primary' => false,
                            ],
                        ],
                    ],
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Customer updated successfully.',
                            'data' => [
                                'customer_id' => 25,
                                'customer_code' => 'CUS00025',
                                'updated' => true,
                                'contacts' => [
                                    'created' => 1,
                                    'updated' => 1,
                                ],
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        404 => ['success' => false, 'message' => 'Customer not found.'],
                        422 => ['success' => false, 'message' => 'Validation failed.'],
                    ],
                ],
            ],
        ],

        // =====================================================================
        // 7. MEETINGS
        // =====================================================================
        'meetings' => [
            'category' => 'operations',
            'name' => 'Meetings',
            'slug' => 'meetings',
            'description' => 'Server-to-server integration for retrieving active scheduled meetings.',
            'operations' => [
                'active' => [
                    'id' => 'meetings.active',
                    'name' => 'Get Active Meetings',
                    'description' => 'Retrieve all currently active scheduled meetings where meeting status is scheduled and end_at is in the future. Ordered chronologically by start_at asc.',
                    'method' => 'GET',
                    'endpoint' => '/api/meetings/active',
                    'scope' => ApiClient::SCOPE_MEETINGS_READ,
                    'scope_description' => 'Allows reading active scheduled meetings.',
                    'headers' => [
                        ['name' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY', 'required' => true, 'note' => 'Inbound server-to-server API Key.'],
                        ['name' => 'Accept', 'value' => 'application/json', 'required' => true, 'note' => 'Required for JSON responses.'],
                    ],
                    'path_params' => [],
                    'query_params' => [],
                    'request_payload' => null,
                    'response_success' => [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'data' => [
                                [
                                    'id' => 101,
                                    'title' => 'Quarterly Strategy Review',
                                    'description' => 'Align on Q4 deliverables and team goals.',
                                    'start_at' => '2026-12-01T10:00:00.000000Z',
                                    'end_at' => '2026-12-01T11:30:00.000000Z',
                                    'url' => 'https://meet.google.com/abc-defg-hij',
                                    'location_details' => 'Conference Room B',
                                    'rescheduled_from_id' => null,
                                    'status' => [
                                        'id' => 1,
                                        'code' => 'scheduled',
                                        'name' => 'Scheduled',
                                        'color' => '#10B981',
                                    ],
                                    'meeting_type' => [
                                        'id' => 2,
                                        'name' => 'Strategy Review',
                                        'color' => '#3B82F6',
                                    ],
                                    'location' => [
                                        'id' => 1,
                                        'name' => 'Google Meet',
                                    ],
                                    'project' => [
                                        'id' => 45,
                                        'name' => 'Corporate Portal Redesign',
                                    ],
                                    'organizer' => [
                                        'id' => 12,
                                        'name' => 'Jane Organizer',
                                        'email' => 'jane@example.com',
                                    ],
                                    'participants' => [
                                        [
                                            'id' => 14,
                                            'name' => 'Ahmed Mohammed',
                                            'email' => 'ahmed@abctech.ae',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'response_errors' => [
                        401 => ['success' => false, 'message' => 'Unauthenticated.'],
                        403 => ['success' => false, 'message' => 'Forbidden.'],
                    ],
                ],
            ],
        ],
    ],
];
