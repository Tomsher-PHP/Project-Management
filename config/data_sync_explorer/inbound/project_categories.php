<?php

use App\Models\ApiClient;

return [
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
            ],
            'request_payload' => null,
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/project-categories/list.response-200.json',
            ],
            'error_profile' => 'unauthenticated',
        ],
        'show' => [
            'id' => 'project_categories.show',
            'name' => 'Get Project Category',
            'description' => 'Retrieve category details by 6-digit code.',
            'method' => 'GET',
            'endpoint' => '/api/data-sync/settings/project_categories/{code}',
            'scope' => ApiClient::SCOPE_SETTINGS_READ,
            'scope_description' => 'Allows reading settings master records.',
            'header_profile' => 'authorization_only',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '600001',
                    'description' => 'Category code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => null,
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/project-categories/show.response-200.json',
            ],
            'error_profile' => 'record_not_found',
        ],
        'create' => [
            'id' => 'project_categories.create',
            'name' => 'Create Project Category',
            'description' => 'Create a project category.',
            'method' => 'POST',
            'endpoint' => '/api/data-sync/settings/project_categories',
            'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
            'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
            'header_profile' => 'json_write_without_accept',
            'path_params' => [
            ],
            'query_params' => [
            ],
            'request_payload' => 'inbound/project-categories/create.request.json',
            'response_success' => [
                'status' => 201,
                'body' => 'inbound/project-categories/create.response-201.json',
            ],
            'error_profile' => 'validation_failed',
        ],
        'update' => [
            'id' => 'project_categories.update',
            'name' => 'Update Project Category',
            'description' => 'Update category name or sort order.',
            'method' => 'PUT',
            'endpoint' => '/api/data-sync/settings/project_categories/{code}',
            'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
            'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
            'header_profile' => 'json_write_without_accept',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '600005',
                    'description' => 'Category code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => 'inbound/project-categories/update.request.json',
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/project-categories/update.response-200.json',
            ],
            'error_profile' => 'record_not_found',
        ],
        'toggle_status' => [
            'id' => 'project_categories.toggle_status',
            'name' => 'Toggle Status',
            'description' => 'Activate or deactivate a project category record via partial PUT without altering other fields.',
            'method' => 'PUT',
            'endpoint' => '/api/data-sync/settings/project_categories/{code}',
            'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
            'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
            'header_profile' => 'json_write',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '600005',
                    'description' => 'Category code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => 'shared/toggle-status.request.json',
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/project-categories/toggle_status.response-200.json',
            ],
            'error_profile' => 'authenticated',
            'response_errors' => [
                404 => 'shared/record-not-found.response-404.json',
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
            'header_profile' => 'authorization_only',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '600005',
                    'description' => 'Category code.',
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
