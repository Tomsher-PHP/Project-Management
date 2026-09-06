<?php

return [

    // DASHBOARD
    ['name' => 'dashboard.view',   'sort_order' => 500, 'default_checked' => false],

    // USER (2000)
    ['name' => 'user.view_all_users', 'sort_order' => 1000, 'default_checked' => false],
    ['name' => 'user.view',           'sort_order' => 1020, 'default_checked' => true],
    ['name' => 'user.create',         'sort_order' => 1040, 'default_checked' => false],
    ['name' => 'user.edit',           'sort_order' => 1060, 'default_checked' => false],
    ['name' => 'user.delete',         'sort_order' => 1080, 'default_checked' => false],
    ['name' => 'user.restore',        'sort_order' => 1100, 'default_checked' => false],
    ['name' => 'user.tree_view',      'sort_order' => 1120, 'default_checked' => true],

    // TEAM (3000)
    ['name' => 'team.view_all_teams', 'sort_order' => 2000, 'default_checked' => false],
    ['name' => 'team.view',           'sort_order' => 2020, 'default_checked' => true],
    ['name' => 'team.create',         'sort_order' => 2040, 'default_checked' => false],
    ['name' => 'team.edit',           'sort_order' => 2060, 'default_checked' => false],
    ['name' => 'team.delete',         'sort_order' => 2080, 'default_checked' => false],

    // CUSTOMER (4000)
    ['name' => 'customer.view',    'sort_order' => 3000, 'default_checked' => false],
    ['name' => 'customer.create',  'sort_order' => 3020, 'default_checked' => false],
    ['name' => 'customer.edit',    'sort_order' => 3040, 'default_checked' => false],
    ['name' => 'customer.delete',  'sort_order' => 3060, 'default_checked' => false],
    ['name' => 'customer.restore', 'sort_order' => 3080, 'default_checked' => false],

    // PROJECT (5000)
    ['name' => 'project.view_all_projects',     'sort_order' => 4000, 'default_checked' => false],
    ['name' => 'project.view',                  'sort_order' => 4020, 'default_checked' => true],
    ['name' => 'project.create',                'sort_order' => 4040, 'default_checked' => false],
    ['name' => 'project.edit',                  'sort_order' => 4060, 'default_checked' => false],
    ['name' => 'project.delete',                'sort_order' => 4080, 'default_checked' => false],
    ['name' => 'project.restore',               'sort_order' => 4100, 'default_checked' => false],
    ['name' => 'project.add_team',              'sort_order' => 4120, 'default_checked' => false],
    ['name' => 'project.remove_team',           'sort_order' => 4140, 'default_checked' => false],
    ['name' => 'project.add_scope',             'sort_order' => 4160, 'default_checked' => false],
    ['name' => 'project.remove_scope',          'sort_order' => 4180, 'default_checked' => false],
    ['name' => 'project.add_notes_files',       'sort_order' => 4200, 'default_checked' => false],
    ['name' => 'project.remove_notes_files',    'sort_order' => 4220, 'default_checked' => false],
    ['name' => 'project.status_change',         'sort_order' => 4240, 'default_checked' => false],
    ['name' => 'project.customer_end_date',     'sort_order' => 4260, 'default_checked' => false],
    ['name' => 'project.add_payment_status',    'sort_order' => 4280, 'default_checked' => false],
    ['name' => 'project.view_payment_status',   'sort_order' => 4290, 'default_checked' => false],

    // PROJECT MILESTONE (6000)
    ['name' => 'project_milestone.view',    'sort_order' => 5000, 'default_checked' => true],
    ['name' => 'project_milestone.create',  'sort_order' => 5020, 'default_checked' => false],
    ['name' => 'project_milestone.edit',    'sort_order' => 5040, 'default_checked' => false],
    ['name' => 'project_milestone.delete',  'sort_order' => 5060, 'default_checked' => false],
    ['name' => 'project_milestone.restore', 'sort_order' => 5080, 'default_checked' => false],

    // PROJECT SPRINT (7000)
    ['name' => 'project_sprint.view',    'sort_order' => 6000, 'default_checked' => true],
    ['name' => 'project_sprint.create',  'sort_order' => 6020, 'default_checked' => false],
    ['name' => 'project_sprint.edit',    'sort_order' => 6040, 'default_checked' => false],
    ['name' => 'project_sprint.delete',  'sort_order' => 6060, 'default_checked' => false],
    ['name' => 'project_sprint.restore', 'sort_order' => 6080, 'default_checked' => false],

    // TASK (8000)
    ['name' => 'task.view_all_tasks',     'sort_order' => 7000, 'default_checked' => false],
    ['name' => 'task.view',               'sort_order' => 7020, 'default_checked' => true],
    ['name' => 'task.create',             'sort_order' => 7040, 'default_checked' => false],
    ['name' => 'task.edit',               'sort_order' => 7060, 'default_checked' => false],
    ['name' => 'task.delete',             'sort_order' => 7080, 'default_checked' => false],
    ['name' => 'task.add_notes_files',    'sort_order' => 7120, 'default_checked' => false],
    ['name' => 'task.remove_notes_files', 'sort_order' => 7140, 'default_checked' => false],
    ['name' => 'task.move',               'sort_order' => 7160, 'default_checked' => false],

    // SCHEDULE SHIFT (10000)
    ['name' => 'schedule_shift.view',   'sort_order' => 8000, 'default_checked' => false],
    ['name' => 'schedule_shift.create', 'sort_order' => 8020, 'default_checked' => false],
    ['name' => 'schedule_shift.edit',   'sort_order' => 8040, 'default_checked' => false],
    ['name' => 'schedule_shift.delete', 'sort_order' => 8060, 'default_checked' => false],

    // ACTIVITY LOG (20000)
    ['name' => 'activity_log.view',   'sort_order' => 9000, 'default_checked' => false],
    ['name' => 'activity_log.delete', 'sort_order' => 9020, 'default_checked' => false],

    // Appraisal operational (31000)
    ['name' => 'appraisal.view',   'sort_order' => 10000, 'default_checked' => true],
    ['name' => 'appraisal.create', 'sort_order' => 10020, 'default_checked' => false],
    ['name' => 'appraisal.edit',   'sort_order' => 10040, 'default_checked' => false],
    ['name' => 'appraisal.delete', 'sort_order' => 10060, 'default_checked' => false],

    //======================================================================
    // Requests Actions Start
    //======================================================================

    // TASK TIME LOG CHANGE REQUEST (24000)
    ['name' => 'task_time_log_change_request.approve_reject', 'sort_order' => 20000, 'default_checked' => false],

    // HANDOFF (27000)
    ['name' => 'handoff_request.view_all',     'sort_order' => 21000, 'default_checked' => false],
    ['name' => 'handoff_request.view',         'sort_order' => 21020, 'default_checked' => false],
    ['name' => 'handoff_request.create',       'sort_order' => 21040, 'default_checked' => false],
    ['name' => 'handoff_request.note',         'sort_order' => 21060, 'default_checked' => false],

    // Break Request (28000)
    ['name' => 'break_request.approve_reject',     'sort_order' => 22000, 'default_checked' => false],

    // Task Time Extend Request (29000)
    ['name' => 'task_time_extend_request.approve_reject', 'sort_order' => 23000, 'default_checked' => false],


    //======================================================================
    // Reports Modules Start
    //======================================================================

    // TIME TRACKING REPORT
    ['name' => 'reports.time_tracking_view', 'sort_order' => 30000, 'default_checked' => true],
    ['name' => 'reports.time_tracking_export', 'sort_order' => 30020, 'default_checked' => false],

    // DAILY TIME REPORT
    ['name' => 'reports.daily_time_view', 'sort_order' => 31000, 'default_checked' => true],
    ['name' => 'reports.daily_time_export', 'sort_order' => 31020, 'default_checked' => false],

    // PRODUCTIVITY REPORT
    ['name' => 'reports.productivity_view', 'sort_order' => 32000, 'default_checked' => true],
    ['name' => 'reports.productivity_export', 'sort_order' => 32020, 'default_checked' => false],

    // PROJECT REPORT
    ['name' => 'reports.project_view', 'sort_order' => 33000, 'default_checked' => false],
    ['name' => 'reports.project_export', 'sort_order' => 33020, 'default_checked' => false],

    // MILESTONE REPORT
    ['name' => 'reports.milestone_view', 'sort_order' => 34000, 'default_checked' => false],
    ['name' => 'reports.milestone_export', 'sort_order' => 34020, 'default_checked' => false],

    // SPRINT REPORT
    ['name' => 'reports.sprint_view', 'sort_order' => 35000, 'default_checked' => false],
    ['name' => 'reports.sprint_export', 'sort_order' => 35020, 'default_checked' => false],

    // TASK REPORT
    ['name' => 'reports.task_view', 'sort_order' => 36000, 'default_checked' => false],
    ['name' => 'reports.task_export', 'sort_order' => 36020, 'default_checked' => false],

    //======================================================================
    // Settings Modules Start
    //======================================================================

    // ROLE (1000)
    ['name' => 'role.view',   'sort_order' => 50000, 'default_checked' => false],
    ['name' => 'role.create', 'sort_order' => 50020, 'default_checked' => false],
    ['name' => 'role.edit',   'sort_order' => 50040, 'default_checked' => false],
    ['name' => 'role.delete', 'sort_order' => 50060, 'default_checked' => false],

    // SHIFT (9000)
    ['name' => 'shift.view',   'sort_order' => 51000, 'default_checked' => false],
    ['name' => 'shift.create', 'sort_order' => 51020, 'default_checked' => false],
    ['name' => 'shift.edit',   'sort_order' => 51040, 'default_checked' => false],
    ['name' => 'shift.delete', 'sort_order' => 51060, 'default_checked' => false],

    // DEPARTMENT (11000)
    ['name' => 'department.view',   'sort_order' => 52000, 'default_checked' => false],
    ['name' => 'department.create', 'sort_order' => 52020, 'default_checked' => false],
    ['name' => 'department.edit',   'sort_order' => 52040, 'default_checked' => false],
    ['name' => 'department.delete', 'sort_order' => 52060, 'default_checked' => false],

    // DESIGNATION (12000)
    ['name' => 'designation.view',   'sort_order' => 53000, 'default_checked' => false],
    ['name' => 'designation.create', 'sort_order' => 53020, 'default_checked' => false],
    ['name' => 'designation.edit',   'sort_order' => 53040, 'default_checked' => false],
    ['name' => 'designation.delete', 'sort_order' => 53060, 'default_checked' => false],

    // TECHNOLOGY (13000)
    ['name' => 'technology.view',   'sort_order' => 54000, 'default_checked' => false],
    ['name' => 'technology.create', 'sort_order' => 54020, 'default_checked' => false],
    ['name' => 'technology.edit',   'sort_order' => 54040, 'default_checked' => false],
    ['name' => 'technology.delete', 'sort_order' => 54060, 'default_checked' => false],

    // PROJECT CATEGORY (14000)
    ['name' => 'project_category.view',   'sort_order' => 55000, 'default_checked' => false],
    ['name' => 'project_category.create', 'sort_order' => 55020, 'default_checked' => false],
    ['name' => 'project_category.edit',   'sort_order' => 55040, 'default_checked' => false],
    ['name' => 'project_category.delete', 'sort_order' => 55060, 'default_checked' => false],

    // INDUSTRY (15000)
    ['name' => 'industry.view',   'sort_order' => 56000, 'default_checked' => false],
    ['name' => 'industry.create', 'sort_order' => 56020, 'default_checked' => false],
    ['name' => 'industry.edit',   'sort_order' => 56040, 'default_checked' => false],
    ['name' => 'industry.delete', 'sort_order' => 56060, 'default_checked' => false],

    // PROJECT STATUS (16000)
    ['name' => 'project_status.view',   'sort_order' => 57000, 'default_checked' => false],
    ['name' => 'project_status.create', 'sort_order' => 57020, 'default_checked' => false],
    ['name' => 'project_status.edit',   'sort_order' => 57040, 'default_checked' => false],
    ['name' => 'project_status.delete', 'sort_order' => 57060, 'default_checked' => false],

    // PROJECT STAGE (17000)
    ['name' => 'project_stage.view',   'sort_order' => 58000, 'default_checked' => false],
    ['name' => 'project_stage.create', 'sort_order' => 58020, 'default_checked' => false],
    ['name' => 'project_stage.edit',   'sort_order' => 58040, 'default_checked' => false],
    ['name' => 'project_stage.delete', 'sort_order' => 58060, 'default_checked' => false],

    // AGILE MILESTONE (21000)
    ['name' => 'agile_milestone.view',   'sort_order' => 59000, 'default_checked' => false],
    ['name' => 'agile_milestone.create', 'sort_order' => 59020, 'default_checked' => false],
    ['name' => 'agile_milestone.edit',   'sort_order' => 59040, 'default_checked' => false],
    ['name' => 'agile_milestone.delete', 'sort_order' => 59060, 'default_checked' => false],

    // AGILE SPRINT (22000)
    ['name' => 'agile_sprint.view',   'sort_order' => 60000, 'default_checked' => false],
    ['name' => 'agile_sprint.create', 'sort_order' => 60020, 'default_checked' => false],
    ['name' => 'agile_sprint.edit',   'sort_order' => 60040, 'default_checked' => false],
    ['name' => 'agile_sprint.delete', 'sort_order' => 60060, 'default_checked' => false],

    // TASK SETTINGS (23000)
    ['name' => 'task_settings.view',   'sort_order' => 61000, 'default_checked' => false],
    ['name' => 'task_settings.create', 'sort_order' => 61020, 'default_checked' => false],
    ['name' => 'task_settings.edit',   'sort_order' => 61040, 'default_checked' => false],
    ['name' => 'task_settings.delete', 'sort_order' => 61060, 'default_checked' => false],

    // KPI (25000)
    ['name' => 'kpi.view',   'sort_order' => 62000, 'default_checked' => false],
    ['name' => 'kpi.create', 'sort_order' => 62020, 'default_checked' => false],
    ['name' => 'kpi.edit',   'sort_order' => 62040, 'default_checked' => false],
    ['name' => 'kpi.delete', 'sort_order' => 62060, 'default_checked' => false],

    // Project checklist template (26000)
    ['name' => 'checklist_template.view',   'sort_order' => 63000, 'default_checked' => false],
    ['name' => 'checklist_template.create', 'sort_order' => 63020, 'default_checked' => false],
    ['name' => 'checklist_template.edit',   'sort_order' => 63040, 'default_checked' => false],
    ['name' => 'checklist_template.delete', 'sort_order' => 63060, 'default_checked' => false],

    // Appraisal Settings (30000)
    ['name' => 'appraisal_settings.view',   'sort_order' => 64000, 'default_checked' => false],
    ['name' => 'appraisal_settings.create', 'sort_order' => 64020, 'default_checked' => false],
    ['name' => 'appraisal_settings.edit',   'sort_order' => 64040, 'default_checked' => false],
    ['name' => 'appraisal_settings.delete', 'sort_order' => 64060, 'default_checked' => false],

    // CONFIGURATION (18000)
    ['name' => 'configuration.view', 'sort_order' => 65000, 'default_checked' => false],
    ['name' => 'configuration.edit', 'sort_order' => 65020, 'default_checked' => false],

];
