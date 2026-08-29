<?php

return [
    // Default list data count per page
    'per_page_count' => 20,

    // workspace auto refresh interval in milliseconds
    'workspace_auto_refresh_interval_ms' => 1 * 60 * 1000, // 5 minutes

    // Default team roles for team management
    'team_roles' => [
        'team_leader' => 'Team Leader',
        'member' => 'Member',
    ],

    // Date format
    'date_format' => 'Y-m-d',

    // Time format    
    'time_format' => 'H:i',

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
        'department.view',
        'designation.view',
        'shift.view',
        'technology.view',
        'project_category.view',
        'industry.view',
        'project_status.view',
        'project_stage.view',
        'configuration.view',
        'agile_milestone.view',
        'agile_sprint.view',
        'task_settings.view',
        'kpi.view',
        'checklist_template.view'
    ],

    // Daily work notification grace period in minutes
    'daily_work_notify' => 120,

    // User settings keys
    'daily_work_hours_warning_mail' => 'daily_work_hours_warning_mail',
];
