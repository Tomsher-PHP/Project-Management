<?php

return [
    // Default list data count per page
    'per_page_count' => 20,

    // workspace auto refresh interval in milliseconds
    'workspace_auto_refresh_interval_ms' => 5 * 60 * 1000, // 5 minutes

    // Default team roles for team management
    'team_roles' => [
        'team_leader' => 'Team Leader',
        'member' => 'Member',
    ],

    // Date format
    'date_format' => 'Y-m-d',

    // Time format
    'time_format' => 'H:i',

    // Timezone
    'timezone' => 'UTC',

    // Currency
    'currency' => 'AED',

    // Constant color code
    'soft_colors' => [
        '#f3f4f6',
        '#fee2e2',
        '#fde68a',
        '#d1fae5',
        '#dbeafe',
        '#e9d5ff',
        '#fbcfe8',
        '#cffafe',
    ],

    // Emirates list
    'emirates' => [
        'abu_dhabi' => 'Abu Dhabi',
        'ajman' => 'Ajman',
        'dubai' => 'Dubai',
        'fujairah' => 'Fujairah',
        'ras_al_khaimah' => 'Ras Al Khaimah',
        'sharjah' => 'Sharjah',
        'umm_al_quwain' => 'Umm Al Quwain',
    ],

    // Date Format constant
    'date_formats' => [
        'Y-m-d',
        'Y/m/d',
        'Y.m.d',
        'd-m-Y',
        'd/m/Y',
        'd.m.Y',
        'Y-M-d',
        'd-M-Y',
    ],

    // Time Format constant
    'time_formats' => [
        'H:i',
        'h:i A',
    ],

    // Settings permissions list
    'settings_permissions' => [
        'role.view',
        'department.view',
        'designation.view',
        'shift.view',
        'technology.view',
        'industry.view',
        'project_settings.view',
        'server_hosting.view',
        'task_settings.view',
        'kpi.view',
        'checklist_template.view',
        'appraisal_settings.view',
        'meeting_settings.view',
        'leave_types.view',
        'company_expenses.view',
        'broadcast_settings.view',
        'data_sync_settings.view',
        'configuration.view',
    ],

    // Daily work notification grace period in minutes
    'daily_work_notify' => 120,

    // User settings keys
    'user_settings' => [
        'daily_work_hours_warning_mail' => 'Daily Work Hours Warning Email',
        'daily_work_report_mail' => 'Daily Work Report Email',
    ],

    // Meeting reminder notification time in minutes
    'meeting_reminder_notification_min' => 10,

    // Supported Data Sync API settings modules
    'data_sync_modules' => [
        'technologies'       => 'technologies',
        'industries'         => 'industries',
        'project_categories' => 'project_categories',
        'project_statuses'   => 'project_statuses',
    ],

    // Data Sync API permissions displayed when creating or editing API clients
    'data_sync_api_permissions' => [
        'settings_masters' => [
            'label' => 'Settings Masters',
            'permissions' => [
                'settings.read' => 'Read Technologies, Industries, Categories, Statuses',
                'settings.write' => 'Create, update, toggle and delete Settings masters',
            ],
        ],
        'operations' => [
            'label' => 'Operations',
            'permissions' => [
                'projects.write' => 'Create & update Customers and Projects',
                'meetings.read' => 'Read scheduled active meetings',
            ],
        ],
    ],

    // Employee information dropdown constants
    'marital_statuses' => [
        'Single' => 'Single',
        'Married' => 'Married',
        'Divorced' => 'Divorced',
        'Widowed' => 'Widowed',
    ],

    'children_counts' => [
        0 => '0',
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
        9 => '9',
        10 => '10+',
    ],

    'favorite_colors' => [
        'Black' => 'Black',
        'White' => 'White',
        'Blue' => 'Blue',
        'Red' => 'Red',
        'Green' => 'Green',
        'Yellow' => 'Yellow',
        'Orange' => 'Orange',
        'Purple' => 'Purple',
        'Pink' => 'Pink',
        'Brown' => 'Brown',
        'Grey' => 'Grey',
    ],

    'shirt_sizes' => [
        'XS' => 'XS',
        'S' => 'S',
        'M' => 'M',
        'L' => 'L',
        'XL' => 'XL',
        'XXL' => 'XXL',
        '3XL' => '3XL',
        '4XL' => '4XL',
        '5XL' => '5XL',
    ],

    'shoe_sizes' => [
        35 => '35',
        36 => '36',
        37 => '37',
        38 => '38',
        39 => '39',
        40 => '40',
        41 => '41',
        42 => '42',
        43 => '43',
        44 => '44',
        45 => '45',
        46 => '46',
        47 => '47',
        48 => '48',
    ],
];
