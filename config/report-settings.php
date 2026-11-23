<?php

return [

    'reports' => [

        'daily_work' => [
            'label' => 'Daily Work Report',
            'description' => 'Employee daily activity and work summary.',
            'available' => true,
            'permission' => 'report_settings.manage',
            'route' => 'settings.daily-work-reports.index',
            'tabs' => ['general', 'recipients', 'users', 'history'],
        ],

        'project_report' => [
            'label' => 'Project Report',
            'description' => 'Project progress, milestones and performance.',
            'available' => true,
            'permission' => 'report_settings.manage',
            'route' => 'settings.project-reports.index',
            'tabs' => ['general', 'recipients', 'history'],
            'sections' => [],
            'reporting_periods' => [
                'all_time' => 'All Time',
                'previous_day' => 'Previous Day',
                'last_7_days' => 'Last 7 Days',
                'last_30_days' => 'Last 30 Days',
                'last_90_days' => 'Last 90 Days',
                'last_6_months' => 'Last 6 Months',
                'last_1_year' => 'Last 1 Year',
            ],
            'date_filters' => [
                'status_changed_date' => 'Status changed date',
                'created_at' => 'Project Created Date within Period',
                'start_date' => 'Project Start Date within Period',
                'end_date' => 'Project End Date within Period',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Project Report Dropdown Constants
    |--------------------------------------------------------------------------
    |
    | Convenient accessors for Project Report dropdown configurations.
    |
    */
    'project_report' => [
        'reporting_periods' => [
            'all_time' => 'All Time',
            'previous_day' => 'Previous Day',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'last_90_days' => 'Last 90 Days',
            'last_6_months' => 'Last 6 Months',
            'last_1_year' => 'Last 1 Year',
        ],
        'date_filters' => [
            'status_changed_date' => 'Status changed date',
            'created_at' => 'Project Created Date within Period',
            'start_date' => 'Project Start Date within Period',
            'end_date' => 'Project End Date within Period',
        ],
    ],

];
