<?php

use App\Models\ApiClient;

return [
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
            'header_profile' => 'json_write',
            'additional_headers' => [
                0 => [
                    'name' => 'Idempotency-Key',
                    'value' => 'crm-sync-batch-20261113-001',
                    'required' => false,
                    'note' => 'Optional UUID or batch token to cache and replay identical response for 10 minutes.',
                ],
            ],
            'path_params' => [
            ],
            'query_params' => [
            ],
            'request_payload' => 'inbound/projects/create.request.json',
            'response_success' => [
                'status' => 201,
                'body' => 'inbound/projects/create.response-201.json',
            ],
            'error_profile' => 'authenticated',
            'response_errors' => [
                409 => 'inbound/projects/create.response-409.json',
                422 => 'shared/validation-failed.response-422.json',
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
            'header_profile' => 'json_write',
            'path_params' => [
                0 => [
                    'name' => 'project_code',
                    'type' => 'string',
                    'required' => true,
                    'example' => 'PRJ00101',
                    'description' => 'Unique PMS project code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => 'inbound/projects/update.request.json',
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/projects/update.response-200.json',
            ],
            'error_profile' => 'unauthenticated',
            'response_errors' => [
                404 => 'inbound/projects/update.response-404.json',
                409 => 'inbound/projects/update.response-409.json',
                422 => 'shared/validation-failed.response-422.json',
            ],
        ],
    ],
];
