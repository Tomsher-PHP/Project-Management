<?php

use App\Models\ApiClient;

return [
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
            'header_profile' => 'json_read',
            'path_params' => [
            ],
            'query_params' => [
            ],
            'request_payload' => null,
            'response_success' => [
                'status' => 200,
                'body' => 'inbound/meetings/active.response-200.json',
            ],
            'error_profile' => 'unauthenticated',
            'response_errors' => [
                403 => 'inbound/meetings/active.response-403.json',
            ],
        ],
    ],
];
