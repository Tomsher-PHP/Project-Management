<?php

return [
    // User Types constant
    // 'user_types' => [
    //     'super_admin' => 'Super Admin',
    //     'admin' => 'Admin',
    //     'manager' => 'Manager',
    //     'team_leader' => 'Team Leader',
    //     'normal_user' => 'Normal User',
    // ],

    // User Type Permissions constant
    'user_type_permissions' => [

        'super_admin' => ['*'],

        'admin' => ['*'],

        'manager' => ['*'],

        'team_leader' => [
            // Project
            'project.view',
            'project.create',
            'project.edit',
            'project.delete',

            // Task
            'task.view',
            'task.create',
            'task.edit',
            'task.delete',

            // User (optional – if allowed)
            'user.view',

            // Team
            'team.view',

            // Reports
            'reports.view',
        ],

        'normal_user' => [
            'project.view',
            'task.view',
            'task.create',
            'task.edit',
        ],

        'tester' => [
            'project.view',
            'task.view',
            'task.create',
            'task.edit',
        ],

        'guest' => [
            'project.view',
            'task.view',
        ],
    ],

    // Default list data count per page
    'per_page_count' => 10,

    // Default team roles for team management
    'team_roles' => [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'member' => 'Member',
    ],

    // Date format
    'date_format' => 'Y-m-d',

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
];
