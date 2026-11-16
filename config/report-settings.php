<?php

return [

    'reports' => [

        'daily_work' => [
            'label' => 'Daily Work Report',
            'description' => 'Employee daily activity and work summary.',
            'available' => true,
            'permission' => 'daily_work_report.edit',
            'tabs' => ['general', 'recipients', 'users', 'advanced'],

            // Keep your existing sections array here.
        ],

        'project_report' => [
            'label' => 'Project Report',
            'description' => 'Project progress, milestones and performance.',
            'available' => false,
            'tabs' => ['general', 'recipients', 'advanced'],
            'sections' => [],
        ],

        'task_report' => [
            'label' => 'Task Report',
            'description' => 'Task status, assignments and overdue tasks.',
            'available' => false,
            'tabs' => ['general', 'recipients', 'users', 'advanced'],
            'sections' => [],
        ],

    ],

];
