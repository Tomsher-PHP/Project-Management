<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Model to Outbound Module Mapping
    |--------------------------------------------------------------------------
    |
    | Maps supported Eloquent model classes to outbound module keys.
    |
    */
    'models' => [
        \App\Models\Project::class => 'projects',
        \App\Models\Customer::class => 'customers',
        \App\Models\Meeting::class => 'meetings',
        \App\Models\Technology::class => 'technologies',
        \App\Models\Industry::class => 'industries',
        \App\Models\ProjectCategory::class => 'project_categories',
        \App\Models\ProjectStatus::class => 'project_statuses',
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Outbound Operations
    |--------------------------------------------------------------------------
    */
    'operations' => [
        'create',
        'update',
        'delete',
        'sync',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('DATA_SYNC_OUTBOUND_TIMEOUT', 30),
    'require_https' => (bool) env('DATA_SYNC_OUTBOUND_REQUIRE_HTTPS', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => env('DATA_SYNC_OUTBOUND_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Pending Delivery Recovery
    |--------------------------------------------------------------------------
    |
    | Threshold (in minutes) after which an uncompleted pending delivery is
    | considered abandoned/interrupted and eligible for scheduled re-dispatch.
    |
    */
    'recovery_threshold_minutes' => (int) env('DATA_SYNC_OUTBOUND_RECOVERY_THRESHOLD_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Maximum attempts, backoff intervals (in seconds), and status codes that
    | are considered temporary / retryable. Permanent errors (e.g. 401, 403, 422)
    | are never automatically retried.
    |
    */
    'retry' => [
        'max_attempts' => (int) env('DATA_SYNC_OUTBOUND_MAX_RETRIES', 3),
        'backoff' => [10, 30, 60],
        'retryable_status_codes' => [429, 500, 502, 503, 504],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module-Specific Relevant Business Fields
    |--------------------------------------------------------------------------
    |
    | When a model is updated, the observer inspects whether any of these
    | relevant business fields were changed. If only audit or irrelevant
    | columns (e.g. updated_at, updated_by) changed, synchronization is skipped.
    |
    */
    'relevant_fields' => [
        'projects' => [
            'name',
            'project_code',
            'customer_id',
            'parent_project_id',
            'project_flow',
            'priority',
            'status_id',
            'project_stage_id',
            'start_date',
            'end_date',
            'customer_end_date',
            'estimated_time_seconds',
            'customer_estimate_seconds',
            'default_task_estimate_seconds',
            'domain',
            'project_category_ids',
            'default_billable',
            'is_active',
            'sales_person_id',
        ],
        'customers' => [
            'customer_code',
            'name',
            'email',
            'industry_id',
            'customer_profile_grade_id',
            'website',
            'registered_country_id',
            'emirate',
            'google_map_link',
            'company_address',
            'sales_person_id',
            'new_to_company',
            'is_active',
        ],
        'meetings' => [
            'project_id',
            'meeting_type_id',
            'meeting_location_id',
            'meeting_status_id',
            'organizer_id',
            'title',
            'description',
            'minutes',
            'start_at',
            'end_at',
            'url',
            'location_details',
            'rescheduled_from_id',
            'reschedule_reason',
        ],
        'technologies' => [
            'name',
            'code',
            'sort_order',
            'is_system',
            'is_active',
        ],
        'industries' => [
            'name',
            'code',
            'parent_id',
            'sort_order',
            'is_system',
            'is_active',
        ],
        'project_categories' => [
            'name',
            'code',
            'sort_order',
            'is_system',
            'is_active',
        ],
        'project_statuses' => [
            'name',
            'code',
            'color',
            'type',
            'sort_order',
            'is_default',
            'is_completed',
            'is_system',
            'is_active',
        ],
    ],
];

