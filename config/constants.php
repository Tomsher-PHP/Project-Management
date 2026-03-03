<?php

return [
    // User Types constant
    'user_types' => [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'manager' => 'Manager',
        'team_leader' => 'Team Leader',
        'normal_user' => 'Normal User',
        'tester' => 'Tester',
        // 'guest' => 'Guest',
    ],

    // Permission Types constant
    'permission_types' => [
        // Project Module
        'project.view',
        'project.create',
        'project.edit',
        'project.delete',

        // Task Module
        'task.view',
        'task.create',
        'task.edit',
        'task.delete',

        // User Module
        'user.view',
        'user.create',
        'user.edit',
        'user.delete',

        //team Module
        'team.view',
        'team.create',
        'team.edit',
        'team.delete',

        // Settings Module
        'settings.view',
        'settings.create',
        'settings.edit',

        // Reports Module
        'reports.view',
        'reports.download',

        // Role Management Module
        'role.view',
        'role.create',
        'role.edit',
        'role.delete',
    ],

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

    //Default list data count per page
    'per_page_count' => 10,

    //Default team roles for team management
    'team_roles' => [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'member' => 'Member',
    ]
];
