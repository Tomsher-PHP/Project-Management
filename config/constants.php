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
        'member' => 'Member',
    ],

    // Default project roles for project management
    'project_roles' => [
        'team_leader' => 'Team Leader',
        'coordinator' => 'Coordinator',
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

    // Project Roles constant
    'project_roles' => [
        'team_leader' => 'Team Leader',
        'coordinator' => 'Coordinator',
        'member' => 'Member',
    ],

    // Project Priority constant
    'project_priorities' => [
        'urgent' => [
            'label' => 'Urgent',
            'color' => 'red',
            'bg_class' => 'bg-error-300',
            'bg_text' => 'text-white',
            'text_class' => 'text-error-300',
        ],
        'high' => [
            'label' => 'High',
            'color' => 'orange',
            'bg_class' => 'bg-orange',
            'bg_text' => 'text-white',
            'text_class' => 'text-warning-300',
        ],
        'medium' => [
            'label' => 'Medium',
            'color' => 'yellow',
            'bg_class' => 'bg-primary',
            'bg_text' => 'text-white',
            'text_class' => 'text-blue-500',
        ],
        'low' => [
            'label' => 'Low',
            'color' => 'green',
            'bg_class' => 'bg-success-400',
            'bg_text' => 'text-white',
            'text_class' => 'text-success-400',
        ],
    ],

    // Project Flow constant
    'project_flows' => [
        'agile' => 'Agile',
        'linear' => 'Linear',
    ],

    // Project Status constant
    'project_statuses' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'on_hold' => 'On Hold',
        'cancelled' => 'Cancelled',
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
];
