<?php

return [

    'reports' => [

        'daily_work' => [
            'label' => 'Daily Work Report',
            'description' => 'Employee daily activity and work summary.',
            'available' => true,
            'permission' => 'report_settings.manage',
            'route' => 'settings.daily-work-reports.index',
            'tabs' => ['general', 'recipients', 'users', 'advanced'],
        ],

        'project_report' => [
            'label' => 'Project Report',
            'description' => 'Project progress, milestones and performance.',
            'available' => true,
            'permission' => 'report_settings.manage',
            'route' => 'settings.project-reports.index',
            'tabs' => ['general', 'recipients'],
            'sections' => [],
        ],

    ],

];
