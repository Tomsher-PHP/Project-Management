<?php

return [
    'project_roles' => [
        'manager' => 'Manager',
        'team_leader' => 'Team Leader',
        'coordinator' => 'Coordinator',
        'member' => 'Member',
        'qa' => 'Quality Assurance',
    ],

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

    'project_flows' => [
        'agile' => 'Agile',
        'linear' => 'Linear',
    ],

    'project_status_types' => [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ],

    'task_status_types' => [
        'pending' => 'Pending', // Default status for new tasks
        'active' => 'Active', // Tasks that are currently being worked on
        'completed' => 'Completed', // Tasks that have been finished
        'archived' => 'Archived', // Tasks that are no longer active but kept for record-keeping
    ],

    'task_priorities' => [
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
            'color' => 'blue',
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

    'task_request_types' => [
        'self',
        'assigned'
    ],

    'request_statuses' => [
        'pending',
        'approved',
        'rejected'
    ],

    'handoff_request_statuses' => [
        0 => 'pending',
        1 => 'noted',
        2 => 'assigned'
    ],

    'handoff_request_actions' => [
        0 => 'created',
        1 => 'noted',
        2 => 'assigned'
    ],

    'project_timeline_statuses' => [
        1 => 'planned',
        2 => 'active',
        3 => 'completed',
        4 => 'cancelled'
    ],

    'project_timeline_types' => [
        'original' => 'Original',
        'renewal' => 'Renewal',
        'extension' => 'Extension',
        'new' => 'New'
    ],

    'project_notification_types' => [
        'timeline_ending_soon' => 'Timeline Ending Soon',
        'hosting_ending_soon' => 'Server & Hosting Expiry',
    ],

    'project_import_modules' => [
        'project_hosting' => [
            'label' => 'Project Hosting Records',
            'description' => 'Import hosting records associated with multiple projects.',
            'permission' => 'project_hosting.create',
            'view_partial' => 'projects.import.modules.project-hosting',
            'template_path' => 'assets/templates/project_hosting/project_hosting_records_import_template.xlsx',
            'template_filename' => 'project_hosting_records_import_template.xlsx',
            'handler_class' => \App\Services\ProjectHostingImportService::class,
            'enabled' => true,
        ],
        // 'project_timeline' => [
        //     'label' => 'Project Timeline Records',
        //     'description' => 'Import timeline records associated with multiple projects.',
        //     'permission' => 'project_timeline.create',
        //     'view_partial' => 'projects.import.modules.project-timeline',
        //     'template_path' => 'assets/templates/project_hosting/project_hosting_records_import_template.xlsx',
        //     'template_filename' => 'project_hosting_records_import_template.xlsx',
        //     'enabled' => true,
        // ],
    ],
];
