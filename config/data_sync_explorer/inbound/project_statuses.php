<?php

use App\Models\ApiClient;

return [
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
            'header_profile' => 'authorization_only',
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
            ],
            'request_payload' => null,
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/project-statuses/list.response-200.json',
            ],
            'error_profile' => 'unauthenticated',
        ],
        'show' => [
            'id' => 'project_statuses.show',
            'name' => 'Get Project Status',
            'description' => 'Retrieve a single project status by code.',
            'method' => 'GET',
            'endpoint' => '/api/data-sync/settings/project_statuses/{code}',
            'scope' => ApiClient::SCOPE_SETTINGS_READ,
            'scope_description' => 'Allows reading settings master records.',
            'header_profile' => 'authorization_only',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '100001',
                    'description' => 'Status code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => null,
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/project-statuses/show.response-200.json',
            ],
            'error_profile' => 'record_not_found',
        ],
        'create' => [
            'id' => 'project_statuses.create',
            'name' => 'Create Project Status',
            'description' => 'Create a project status with specified type and hex color.',
            'method' => 'POST',
            'endpoint' => '/api/data-sync/settings/project_statuses',
            'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
            'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
            'header_profile' => 'json_write_without_accept',
            'path_params' => [
            ],
            'query_params' => [
            ],
            'request_payload' => 'inbound/project-statuses/create.request.json',
            'response_success' => [
                'status' => 201,
                'body' => 'inbound/project-statuses/create.response-201.json',
            ],
            'error_profile' => 'validation_failed',
        ],
        'update' => [
            'id' => 'project_statuses.update',
            'name' => 'Update Project Status',
            'description' => 'Update name, color, type or default setting of a status.',
            'method' => 'PUT',
            'endpoint' => '/api/data-sync/settings/project_statuses/{code}',
            'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
            'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
            'header_profile' => 'json_write_without_accept',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '100020',
                    'description' => 'Status code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => 'inbound/project-statuses/update.request.json',
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/project-statuses/update.response-200.json',
            ],
            'error_profile' => 'record_not_found',
        ],
        'toggle_status' => [
            'id' => 'project_statuses.toggle_status',
            'name' => 'Toggle Status',
            'description' => 'Activate or deactivate a project status record via partial PUT without altering other fields.',
            'method' => 'PUT',
            'endpoint' => '/api/data-sync/settings/project_statuses/{code}',
            'scope' => ApiClient::SCOPE_SETTINGS_WRITE,
            'scope_description' => 'Allows creating, modifying, and deleting settings master records.',
            'header_profile' => 'json_write',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '100020',
                    'description' => 'Status code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => 'shared/toggle-status.request.json',
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/project-statuses/toggle_status.response-200.json',
            ],
            'error_profile' => 'authenticated',
            'response_errors' => [
                404 => 'shared/record-not-found.response-404.json',
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
            'header_profile' => 'authorization_only',
            'path_params' => [
                0 => [
                    'name' => 'code',
                    'type' => 'string (6 digits)',
                    'required' => true,
                    'example' => '100020',
                    'description' => 'Status code.',
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
