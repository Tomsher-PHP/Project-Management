<?php

use App\Models\ApiClient;

return [
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
            'header_profile' => 'json_write',
            'path_params' => [
                0 => [
                    'name' => 'customer_code',
                    'type' => 'string',
                    'required' => true,
                    'example' => 'CUS00025',
                    'description' => 'Unique PMS customer code.',
                ],
            ],
            'query_params' => [
            ],
            'request_payload' => 'inbound/customers/update.request.json',
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/customers/update.response-200.json',
            ],
            'error_profile' => 'unauthenticated',
            'response_errors' => [
                404 => 'inbound/customers/update.response-404.json',
                422 => 'shared/validation-failed.response-422.json',
            ],
        ],
    ],
];
