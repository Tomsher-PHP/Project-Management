<?php

return [

    // DASHBOARD
    ['name' => 'dashboard.view',   'sort_order' => 500, 'default_checked' => false],

    // ROLE (1000)
    ['name' => 'role.view',   'sort_order' => 1000, 'default_checked' => false],
    ['name' => 'role.create', 'sort_order' => 1020, 'default_checked' => false],
    ['name' => 'role.edit',   'sort_order' => 1040, 'default_checked' => false],
    ['name' => 'role.delete', 'sort_order' => 1060, 'default_checked' => false],

    // USER (2000)
    ['name' => 'user.view_all_users', 'sort_order' => 2000, 'default_checked' => false],
    ['name' => 'user.view',           'sort_order' => 2020, 'default_checked' => true],
    ['name' => 'user.create',         'sort_order' => 2040, 'default_checked' => false],
    ['name' => 'user.edit',           'sort_order' => 2060, 'default_checked' => false],
    ['name' => 'user.delete',         'sort_order' => 2080, 'default_checked' => false],
    ['name' => 'user.restore',        'sort_order' => 2100, 'default_checked' => false],
    ['name' => 'user.tree_view',      'sort_order' => 2120, 'default_checked' => true],

    // TEAM (3000)
    ['name' => 'team.view_all_teams', 'sort_order' => 3000, 'default_checked' => false],
    ['name' => 'team.view',           'sort_order' => 3020, 'default_checked' => true],
    ['name' => 'team.create',         'sort_order' => 3040, 'default_checked' => false],
    ['name' => 'team.edit',           'sort_order' => 3060, 'default_checked' => false],
    ['name' => 'team.delete',         'sort_order' => 3080, 'default_checked' => false],

    // CUSTOMER (4000)
    ['name' => 'customer.view',    'sort_order' => 4000, 'default_checked' => false],
    ['name' => 'customer.create',  'sort_order' => 4020, 'default_checked' => false],
    ['name' => 'customer.edit',    'sort_order' => 4040, 'default_checked' => false],
    ['name' => 'customer.delete',  'sort_order' => 4060, 'default_checked' => false],
    ['name' => 'customer.restore', 'sort_order' => 4080, 'default_checked' => false],

    // PROJECT (5000)
    ['name' => 'project.view_all_projects',     'sort_order' => 5000, 'default_checked' => false],
    ['name' => 'project.view',                  'sort_order' => 5020, 'default_checked' => true],
    ['name' => 'project.create',                'sort_order' => 5040, 'default_checked' => false],
    ['name' => 'project.edit',                  'sort_order' => 5060, 'default_checked' => false],
    ['name' => 'project.delete',                'sort_order' => 5080, 'default_checked' => false],
    ['name' => 'project.restore',               'sort_order' => 5100, 'default_checked' => false],
    ['name' => 'project.add_team',              'sort_order' => 5120, 'default_checked' => false],
    ['name' => 'project.remove_team',           'sort_order' => 5140, 'default_checked' => false],
    ['name' => 'project.add_scope',             'sort_order' => 5160, 'default_checked' => false],
    ['name' => 'project.remove_scope',          'sort_order' => 5180, 'default_checked' => false],
    ['name' => 'project.add_notes_files',       'sort_order' => 5200, 'default_checked' => false],
    ['name' => 'project.remove_notes_files',    'sort_order' => 5220, 'default_checked' => false],
    ['name' => 'project.status_change',         'sort_order' => 5240, 'default_checked' => false],
    ['name' => 'project.customer_end_date',     'sort_order' => 5260, 'default_checked' => false],
    ['name' => 'project.add_payment_status',    'sort_order' => 5280, 'default_checked' => false],
    ['name' => 'project.view_payment_status',   'sort_order' => 5290, 'default_checked' => false],

    // PROJECT MILESTONE (6000)
    ['name' => 'project_milestone.view',    'sort_order' => 6000, 'default_checked' => true],
    ['name' => 'project_milestone.create',  'sort_order' => 6020, 'default_checked' => false],
    ['name' => 'project_milestone.edit',    'sort_order' => 6040, 'default_checked' => false],
    ['name' => 'project_milestone.delete',  'sort_order' => 6060, 'default_checked' => false],
    ['name' => 'project_milestone.restore', 'sort_order' => 6080, 'default_checked' => false],

    // PROJECT SPRINT (7000)
    ['name' => 'project_sprint.view',    'sort_order' => 7000, 'default_checked' => true],
    ['name' => 'project_sprint.create',  'sort_order' => 7020, 'default_checked' => false],
    ['name' => 'project_sprint.edit',    'sort_order' => 7040, 'default_checked' => false],
    ['name' => 'project_sprint.delete',  'sort_order' => 7060, 'default_checked' => false],
    ['name' => 'project_sprint.restore', 'sort_order' => 7080, 'default_checked' => false],

    // TASK (8000)
    ['name' => 'task.view_all_tasks',     'sort_order' => 8000, 'default_checked' => false],
    ['name' => 'task.view',               'sort_order' => 8020, 'default_checked' => true],
    ['name' => 'task.create',             'sort_order' => 8040, 'default_checked' => false],
    ['name' => 'task.edit',               'sort_order' => 8060, 'default_checked' => false],
    ['name' => 'task.delete',             'sort_order' => 8080, 'default_checked' => false],
    ['name' => 'task.add_notes_files',    'sort_order' => 8120, 'default_checked' => false],
    ['name' => 'task.remove_notes_files', 'sort_order' => 8140, 'default_checked' => false],
    ['name' => 'task.move',               'sort_order' => 8160, 'default_checked' => false],

    // SHIFT (9000)
    ['name' => 'shift.view',   'sort_order' => 9000, 'default_checked' => false],
    ['name' => 'shift.create', 'sort_order' => 9020, 'default_checked' => false],
    ['name' => 'shift.edit',   'sort_order' => 9040, 'default_checked' => false],
    ['name' => 'shift.delete', 'sort_order' => 9060, 'default_checked' => false],

    // SCHEDULE SHIFT (10000)
    ['name' => 'schedule_shift.view',   'sort_order' => 10000, 'default_checked' => true],
    ['name' => 'schedule_shift.create', 'sort_order' => 10020, 'default_checked' => false],
    ['name' => 'schedule_shift.edit',   'sort_order' => 10040, 'default_checked' => false],
    ['name' => 'schedule_shift.delete', 'sort_order' => 10060, 'default_checked' => false],

    // DEPARTMENT (11000)
    ['name' => 'department.view',   'sort_order' => 11000, 'default_checked' => false],
    ['name' => 'department.create', 'sort_order' => 11020, 'default_checked' => false],
    ['name' => 'department.edit',   'sort_order' => 11040, 'default_checked' => false],
    ['name' => 'department.delete', 'sort_order' => 11060, 'default_checked' => false],

    // DESIGNATION (12000)
    ['name' => 'designation.view',   'sort_order' => 12000, 'default_checked' => false],
    ['name' => 'designation.create', 'sort_order' => 12020, 'default_checked' => false],
    ['name' => 'designation.edit',   'sort_order' => 12040, 'default_checked' => false],
    ['name' => 'designation.delete', 'sort_order' => 12060, 'default_checked' => false],

    // TECHNOLOGY (13000)
    ['name' => 'technology.view',   'sort_order' => 13000, 'default_checked' => false],
    ['name' => 'technology.create', 'sort_order' => 13020, 'default_checked' => false],
    ['name' => 'technology.edit',   'sort_order' => 13040, 'default_checked' => false],
    ['name' => 'technology.delete', 'sort_order' => 13060, 'default_checked' => false],

    // PROJECT CATEGORY (14000)
    ['name' => 'project_category.view',   'sort_order' => 14000, 'default_checked' => false],
    ['name' => 'project_category.create', 'sort_order' => 14020, 'default_checked' => false],
    ['name' => 'project_category.edit',   'sort_order' => 14040, 'default_checked' => false],
    ['name' => 'project_category.delete', 'sort_order' => 14060, 'default_checked' => false],

    // INDUSTRY (15000)
    ['name' => 'industry.view',   'sort_order' => 15000, 'default_checked' => false],
    ['name' => 'industry.create', 'sort_order' => 15020, 'default_checked' => false],
    ['name' => 'industry.edit',   'sort_order' => 15040, 'default_checked' => false],
    ['name' => 'industry.delete', 'sort_order' => 15060, 'default_checked' => false],

    // PROJECT STATUS (16000)
    ['name' => 'project_status.view',   'sort_order' => 16000, 'default_checked' => false],
    ['name' => 'project_status.create', 'sort_order' => 16020, 'default_checked' => false],
    ['name' => 'project_status.edit',   'sort_order' => 16040, 'default_checked' => false],
    ['name' => 'project_status.delete', 'sort_order' => 16060, 'default_checked' => false],

    // PROJECT STAGE (17000)
    ['name' => 'project_stage.view',   'sort_order' => 17000, 'default_checked' => false],
    ['name' => 'project_stage.create', 'sort_order' => 17020, 'default_checked' => false],
    ['name' => 'project_stage.edit',   'sort_order' => 17040, 'default_checked' => false],
    ['name' => 'project_stage.delete', 'sort_order' => 17060, 'default_checked' => false],

    // CONFIGURATION (18000)
    ['name' => 'configuration.view', 'sort_order' => 18000, 'default_checked' => false],
    ['name' => 'configuration.edit', 'sort_order' => 18020, 'default_checked' => false],

    // PROJECT REPORT
    ['name' => 'reports.project_view', 'sort_order' => 19000, 'default_checked' => false],
    ['name' => 'reports.project_export', 'sort_order' => 19001, 'default_checked' => false],

    // TASK REPORT
    ['name' => 'reports.task_view', 'sort_order' => 19010, 'default_checked' => false],
    ['name' => 'reports.task_export', 'sort_order' => 19011, 'default_checked' => false],

    // TIME TRACKING REPORT
    ['name' => 'reports.time_tracking_view', 'sort_order' => 19020, 'default_checked' => true],
    ['name' => 'reports.time_tracking_export', 'sort_order' => 19021, 'default_checked' => false],

    // ATTENDANCE REPORT
    ['name' => 'reports.attendance_view', 'sort_order' => 19030, 'default_checked' => false],
    ['name' => 'reports.attendance_export', 'sort_order' => 19031, 'default_checked' => false],

    // DAILY TIME REPORT
    ['name' => 'reports.daily_time_view', 'sort_order' => 19040, 'default_checked' => false],
    ['name' => 'reports.daily_time_export', 'sort_order' => 19041, 'default_checked' => false],

    // SHIFT SCHEDULE REPORT
    ['name' => 'reports.shift_schedule_view', 'sort_order' => 19050, 'default_checked' => false],
    ['name' => 'reports.shift_schedule_export', 'sort_order' => 19051, 'default_checked' => false],

    // PRODUCTIVITY REPORT
    ['name' => 'reports.productivity_view', 'sort_order' => 19060, 'default_checked' => false],
    ['name' => 'reports.productivity_export', 'sort_order' => 19061, 'default_checked' => false],

    // SPRINT REPORT
    ['name' => 'reports.sprint_view', 'sort_order' => 19070, 'default_checked' => false],
    ['name' => 'reports.sprint_export', 'sort_order' => 19071, 'default_checked' => false],

    // MILESTONE REPORT
    ['name' => 'reports.milestone_view', 'sort_order' => 19080, 'default_checked' => false],
    ['name' => 'reports.milestone_export', 'sort_order' => 19081, 'default_checked' => false],

    // LEAVE REPORT
    ['name' => 'reports.leave_view', 'sort_order' => 19090, 'default_checked' => false],
    ['name' => 'reports.leave_export', 'sort_order' => 19091, 'default_checked' => false],

    // ACTIVITY LOG (20000)
    ['name' => 'activity_log.view',   'sort_order' => 20000, 'default_checked' => false],
    ['name' => 'activity_log.delete', 'sort_order' => 20020, 'default_checked' => false],

    // AGILE MILESTONE (21000)
    ['name' => 'agile_milestone.view',   'sort_order' => 21000, 'default_checked' => false],
    ['name' => 'agile_milestone.create', 'sort_order' => 21020, 'default_checked' => false],
    ['name' => 'agile_milestone.edit',   'sort_order' => 21040, 'default_checked' => false],
    ['name' => 'agile_milestone.delete', 'sort_order' => 21060, 'default_checked' => false],

    // AGILE SPRINT (22000)
    ['name' => 'agile_sprint.view',   'sort_order' => 22000, 'default_checked' => false],
    ['name' => 'agile_sprint.create', 'sort_order' => 22020, 'default_checked' => false],
    ['name' => 'agile_sprint.edit',   'sort_order' => 22040, 'default_checked' => false],
    ['name' => 'agile_sprint.delete', 'sort_order' => 22060, 'default_checked' => false],

    // TASK SETTINGS (23000)
    ['name' => 'task_settings.view',   'sort_order' => 23000, 'default_checked' => false],
    ['name' => 'task_settings.create', 'sort_order' => 23020, 'default_checked' => false],
    ['name' => 'task_settings.edit',   'sort_order' => 23040, 'default_checked' => false],
    ['name' => 'task_settings.delete', 'sort_order' => 23060, 'default_checked' => false],

    // TASK TIME LOG CHANGE REQUEST (24000)
    ['name' => 'task_time_log_change_request.approve_reject', 'sort_order' => 24000, 'default_checked' => false],

    // KPI (25000)
    ['name' => 'kpi.view',   'sort_order' => 25000, 'default_checked' => false],
    ['name' => 'kpi.create', 'sort_order' => 25020, 'default_checked' => false],
    ['name' => 'kpi.edit',   'sort_order' => 25040, 'default_checked' => false],
    ['name' => 'kpi.delete', 'sort_order' => 25060, 'default_checked' => false],

    // Project checklist template (26000)
    ['name' => 'checklist_template.view',   'sort_order' => 26000, 'default_checked' => false],
    ['name' => 'checklist_template.create', 'sort_order' => 26020, 'default_checked' => false],
    ['name' => 'checklist_template.edit',   'sort_order' => 26040, 'default_checked' => false],
    ['name' => 'checklist_template.delete', 'sort_order' => 26060, 'default_checked' => false],

    // HANDOFF (27000)
    ['name' => 'handoff_request.view_all',     'sort_order' => 27000, 'default_checked' => false],
    ['name' => 'handoff_request.view',         'sort_order' => 27020, 'default_checked' => false],
    ['name' => 'handoff_request.create',       'sort_order' => 27040, 'default_checked' => false],
    ['name' => 'handoff_request.note',         'sort_order' => 27060, 'default_checked' => false],

    // Break Request (28000)
    ['name' => 'break_request.approve_reject',     'sort_order' => 28000, 'default_checked' => false],

];
