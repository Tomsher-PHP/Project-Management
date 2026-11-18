<?php

use App\Models\ApiClient;

return [
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
            'header_profile' => 'json_read',
            'path_params' => [
            ],
            'query_params' => [
                0 => [
                    'name' => 'page',
                    'type' => 'integer',
                    'required' => false,
                    'default' => '1',
                    'description' => 'Page number (>= 1).',
                ],
                1 => [
                    'name' => 'per_page',
                    'type' => 'integer',
                    'required' => false,
                    'default' => '50',
                    'description' => 'Records per page (1 to 100).',
                ],
                2 => [
                    'name' => 'search',
                    'type' => 'string',
                    'required' => false,
                    'default' => 'null',
                    'description' => 'Search term matching name or code.',
                ],
                3 => [
                    'name' => 'is_active',
                    'type' => 'boolean',
                    'required' => false,
                    'default' => 'null',
                    'description' => 'Filter by active status (1 or 0).',
                ],
                4 => [
                    'name' => 'sort_by',
                    'type' => 'string',
                    'required' => false,
                    'default' => 'sort_order',
                    'description' => 'id, name, code, sort_order, is_active, created_at.',
                ],
                5 => [
                    'name' => 'sort_direction',
                    'type' => 'string',
                    'required' => false,
                    'default' => 'asc',
                    'description' => 'asc or desc.',
                ],
            ],
            'request_payload' => null,
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/technologies/list.response-200.json',
            ],
            'error_profile' => 'authenticated',
        ],
        'show' => [
            'id' => 'technologies.show',
            'name' => 'Get Technology',
            'description' => 'Retrieve a single technology record by its unique 6-digit numeric code.',
            'method' => 'GET',
            'endpoint' => '/api/data-sync/settings/technologies/{code}',
            'scope' => ApiClient::SCOPE_SETTINGS_READ,
            'scope_description' => 'Allows reading settings master records.',
            'header_profile' => 'json_read',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '500001',
                    'description' => 'Unique 6-digit code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => null,
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/technologies/show.response-200.json',
            ],
            'error_profile' => 'authenticated',
            'response_errors' => [
                404 => 'shared/record-not-found.response-404.json',
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
            'header_profile' => 'json_write',
            'additional_headers' => [
                0 => [
                    'name' => 'Idempotency-Key',
                    'value' => 'unique-req-uuid-or-hash',
                    'required' => false,
                    'note' => 'Optional UUID or hash to prevent duplicate creation.',
                ],
            ],
            'path_params' => [
            ],
            'query_params' => [
            ],
            'request_payload' => 'inbound/technologies/create.request.json',
            'response_success' => [
                'status' => 201,
                'body' => 'inbound/technologies/create.response-201.json',
            ],
            'error_profile' => 'authenticated',
            'response_errors' => [
                409 => 'inbound/technologies/create.response-409.json',
                422 => 'inbound/technologies/create.response-422.json',
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
            'header_profile' => 'json_write',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '500010',
                    'description' => 'Code of the record to update.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => 'inbound/technologies/update.request.json',
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/technologies/update.response-200.json',
            ],
            'error_profile' => 'authenticated',
            'response_errors' => [
                404 => 'shared/record-not-found.response-404.json',
                422 => 'shared/validation-failed.response-422.json',
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
            'header_profile' => 'json_write',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '500010',
                    'description' => 'Code of the record.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => 'shared/toggle-status.request.json',
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/technologies/toggle_status.response-200.json',
            ],
            'error_profile' => 'authenticated',
            'response_errors' => [
                404 => 'shared/record-not-found.response-404.json',
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
            'header_profile' => 'json_read',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '500010',
                    'description' => 'Code of the record to delete.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => null,
            'response_success' => [
                'status' => 200,
                'body' => 'shared/delete.response-200.json',
            ],
            'error_profile' => 'unauthenticated',
            'response_errors' => [
                403 => 'inbound/technologies/delete.response-403.json',
                404 => 'shared/record-not-found.response-404.json',
                409 => 'shared/record-in-use.response-409.json',
            ],
        ],
    ],
];
