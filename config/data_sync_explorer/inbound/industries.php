<?php

use App\Models\ApiClient;

return [
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
            'header_profile' => 'json_read',
            'path_params' => [
            ],
            'query_params' => [
                0 => [
                    'name' => 'page',
                    'type' => 'integer',
                    'required' => false,
                    'default' => '1',
                    'description' => 'Page number.',
                ],
                1 => [
                    'name' => 'per_page',
                    'type' => 'integer',
                    'required' => false,
                    'default' => '50',
                    'description' => 'Records per page.',
                ],
                2 => [
                    'name' => 'search',
                    'type' => 'string',
                    'required' => false,
                    'default' => 'null',
                    'description' => 'Search name or code.',
                ],
            ],
            'request_payload' => null,
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/industries/list.response-200.json',
            ],
            'error_profile' => 'authenticated',
        ],
        'show' => [
            'id' => 'industries.show',
            'name' => 'Get Industry',
            'description' => 'Retrieve an industry by 6-digit code.',
            'method' => 'GET',
            'endpoint' => '/api/data-sync/settings/industries/{code}',
            'scope' => ApiClient::SCOPE_SETTINGS_READ,
            'scope_description' => 'Allows reading settings master records.',
            'header_profile' => 'json_read',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '743813',
                    'description' => 'Industry code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => null,
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/industries/show.response-200.json',
            ],
            'error_profile' => 'unauthenticated',
            'response_errors' => [
                404 => 'shared/record-not-found.response-404.json',
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
            'header_profile' => 'json_write',
            'additional_headers' => [
                0 => [
                    'name' => 'Idempotency-Key',
                    'value' => 'unique-req-uuid-or-hash',
                    'required' => false,
                    'note' => 'Optional UUID.',
                ],
            ],
            'path_params' => [
            ],
            'query_params' => [
            ],
            'request_payload' => 'inbound/industries/create.request.json',
            'response_success' => [
                'status' => 201,
                'body' => 'inbound/industries/create.response-201.json',
            ],
            'error_profile' => 'unauthenticated',
            'response_errors' => [
                422 => 'inbound/industries/create.response-422.json',
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
            'header_profile' => 'json_write_without_accept',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '743820',
                    'description' => 'Industry code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => 'inbound/industries/update.request.json',
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/industries/update.response-200.json',
            ],
            'response_errors' => [
                404 => 'shared/record-not-found.response-404.json',
                422 => 'inbound/industries/update.response-422.json',
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
            'header_profile' => 'json_write',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '743820',
                    'description' => 'Industry code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => 'shared/toggle-status.request.json',
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/industries/toggle_status.response-200.json',
            ],
            'error_profile' => 'authenticated',
            'response_errors' => [
                404 => 'shared/record-not-found.response-404.json',
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
            'header_profile' => 'authorization_only',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '743820',
                    'description' => 'Industry code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => null,
            'response_success' => [
                'status' => 200,
                'body' => 'shared/delete.response-200.json',
            ],
            'error_profile' => 'record_in_use',
        ],
    ],
];
