<?php

return [
    'project_roles' => [
        'team_leader' => 'Team Leader',
        'coordinator' => 'Coordinator',
        'member' => 'Member',
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

    'task_request_types' => [
        'self',
        'assigned'
    ],

    'request_statuses' => [
        'pending',
        'approved',
        'rejected'
    ],
];
