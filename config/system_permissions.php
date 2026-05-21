<?php

return [

    // DASHBOARD
    ['name' => 'dashboard.view',   'sort_order' => 500],

    // ROLE (1000)
    ['name' => 'role.view',   'sort_order' => 1000],
    ['name' => 'role.create', 'sort_order' => 1020],
    ['name' => 'role.edit',   'sort_order' => 1040],
    ['name' => 'role.delete', 'sort_order' => 1060],

    // USER (2000)
    ['name' => 'user.view_all_users', 'sort_order' => 2000],
    ['name' => 'user.view',           'sort_order' => 2020],
    ['name' => 'user.create',         'sort_order' => 2040],
    ['name' => 'user.edit',           'sort_order' => 2060],
    ['name' => 'user.delete',         'sort_order' => 2080],
    ['name' => 'user.restore',        'sort_order' => 2100],
    ['name' => 'user.tree_view',      'sort_order' => 2120],

    // TEAM (3000)
    ['name' => 'team.view_all_teams', 'sort_order' => 3000],
    ['name' => 'team.view',           'sort_order' => 3020],
    ['name' => 'team.create',         'sort_order' => 3040],
    ['name' => 'team.edit',           'sort_order' => 3060],
    ['name' => 'team.delete',         'sort_order' => 3080],

    // CUSTOMER (4000)
    ['name' => 'customer.view',    'sort_order' => 4000],
    ['name' => 'customer.create',  'sort_order' => 4020],
    ['name' => 'customer.edit',    'sort_order' => 4040],
    ['name' => 'customer.delete',  'sort_order' => 4060],
    ['name' => 'customer.restore', 'sort_order' => 4080],

    // PROJECT (5000)
    ['name' => 'project.view_all_projects',     'sort_order' => 5000],
    ['name' => 'project.view',                  'sort_order' => 5020],
    ['name' => 'project.create',                'sort_order' => 5040],
    ['name' => 'project.edit',                  'sort_order' => 5060],
    ['name' => 'project.delete',                'sort_order' => 5080],
    ['name' => 'project.restore',               'sort_order' => 5100],
    ['name' => 'project.add_team',              'sort_order' => 5120],
    ['name' => 'project.remove_team',           'sort_order' => 5140],
    ['name' => 'project.add_scope',             'sort_order' => 5160],
    ['name' => 'project.remove_scope',          'sort_order' => 5180],
    ['name' => 'project.add_notes_files',       'sort_order' => 5200],
    ['name' => 'project.remove_notes_files',    'sort_order' => 5220],
    ['name' => 'project.status_change',         'sort_order' => 5240],
    ['name' => 'project.customer_end_date',     'sort_order' => 5260],
    ['name' => 'project.add_payment_status',    'sort_order' => 5280],
    ['name' => 'project.view_payment_status',   'sort_order' => 5290],

    // PROJECT MILESTONE (6000)
    ['name' => 'project_milestone.view',    'sort_order' => 6000],
    ['name' => 'project_milestone.create',  'sort_order' => 6020],
    ['name' => 'project_milestone.edit',    'sort_order' => 6040],
    ['name' => 'project_milestone.delete',  'sort_order' => 6060],
    ['name' => 'project_milestone.restore', 'sort_order' => 6080],

    // PROJECT SPRINT (7000)
    ['name' => 'project_sprint.view',    'sort_order' => 7000],
    ['name' => 'project_sprint.create',  'sort_order' => 7020],
    ['name' => 'project_sprint.edit',    'sort_order' => 7040],
    ['name' => 'project_sprint.delete',  'sort_order' => 7060],
    ['name' => 'project_sprint.restore', 'sort_order' => 7080],

    // TASK (8000)
    ['name' => 'task.view_all_tasks',     'sort_order' => 8000],
    ['name' => 'task.view',               'sort_order' => 8020],
    ['name' => 'task.create',             'sort_order' => 8040],
    ['name' => 'task.edit',               'sort_order' => 8060],
    ['name' => 'task.delete',             'sort_order' => 8080],
    ['name' => 'task.add_notes_files',    'sort_order' => 8120],
    ['name' => 'task.remove_notes_files', 'sort_order' => 8140],
    ['name' => 'task.move',               'sort_order' => 8160],

    // SHIFT (9000)
    ['name' => 'shift.view',   'sort_order' => 9000],
    ['name' => 'shift.create', 'sort_order' => 9020],
    ['name' => 'shift.edit',   'sort_order' => 9040],
    ['name' => 'shift.delete', 'sort_order' => 9060],

    // SCHEDULE SHIFT (10000)
    ['name' => 'schedule_shift.view',   'sort_order' => 10000],
    ['name' => 'schedule_shift.create', 'sort_order' => 10020],
    ['name' => 'schedule_shift.edit',   'sort_order' => 10040],
    ['name' => 'schedule_shift.delete', 'sort_order' => 10060],

    // DEPARTMENT (11000)
    ['name' => 'department.view',   'sort_order' => 11000],
    ['name' => 'department.create', 'sort_order' => 11020],
    ['name' => 'department.edit',   'sort_order' => 11040],
    ['name' => 'department.delete', 'sort_order' => 11060],

    // DESIGNATION (12000)
    ['name' => 'designation.view',   'sort_order' => 12000],
    ['name' => 'designation.create', 'sort_order' => 12020],
    ['name' => 'designation.edit',   'sort_order' => 12040],
    ['name' => 'designation.delete', 'sort_order' => 12060],

    // TECHNOLOGY (13000)
    ['name' => 'technology.view',   'sort_order' => 13000],
    ['name' => 'technology.create', 'sort_order' => 13020],
    ['name' => 'technology.edit',   'sort_order' => 13040],
    ['name' => 'technology.delete', 'sort_order' => 13060],

    // PROJECT CATEGORY (14000)
    ['name' => 'project_category.view',   'sort_order' => 14000],
    ['name' => 'project_category.create', 'sort_order' => 14020],
    ['name' => 'project_category.edit',   'sort_order' => 14040],
    ['name' => 'project_category.delete', 'sort_order' => 14060],

    // INDUSTRY (15000)
    ['name' => 'industry.view',   'sort_order' => 15000],
    ['name' => 'industry.create', 'sort_order' => 15020],
    ['name' => 'industry.edit',   'sort_order' => 15040],
    ['name' => 'industry.delete', 'sort_order' => 15060],

    // PROJECT STATUS (16000)
    ['name' => 'project_status.view',   'sort_order' => 16000],
    ['name' => 'project_status.create', 'sort_order' => 16020],
    ['name' => 'project_status.edit',   'sort_order' => 16040],
    ['name' => 'project_status.delete', 'sort_order' => 16060],

    // PROJECT STAGE (17000)
    ['name' => 'project_stage.view',   'sort_order' => 17000],
    ['name' => 'project_stage.create', 'sort_order' => 17020],
    ['name' => 'project_stage.edit',   'sort_order' => 17040],
    ['name' => 'project_stage.delete', 'sort_order' => 17060],

    // CONFIGURATION (18000)
    ['name' => 'configuration.view', 'sort_order' => 18000],
    ['name' => 'configuration.edit', 'sort_order' => 18020],

    // PROJECT REPORT
    ['name' => 'reports.project_view', 'sort_order' => 19000],
    ['name' => 'reports.project_export', 'sort_order' => 19001],

    // TASK REPORT
    ['name' => 'reports.task_view', 'sort_order' => 19010],
    ['name' => 'reports.task_export', 'sort_order' => 19011],

    // TIME TRACKING REPORT
    ['name' => 'reports.time_tracking_view', 'sort_order' => 19020],
    ['name' => 'reports.time_tracking_export', 'sort_order' => 19021],

    // ATTENDANCE REPORT
    ['name' => 'reports.attendance_view', 'sort_order' => 19030],
    ['name' => 'reports.attendance_export', 'sort_order' => 19031],

    // DAILY REPORT
    ['name' => 'reports.daily_view', 'sort_order' => 19040],
    ['name' => 'reports.daily_export', 'sort_order' => 19041],

    // SHIFT SCHEDULE REPORT
    ['name' => 'reports.shift_schedule_view', 'sort_order' => 19050],
    ['name' => 'reports.shift_schedule_export', 'sort_order' => 19051],

    // PRODUCTIVITY REPORT
    ['name' => 'reports.productivity_view', 'sort_order' => 19060],
    ['name' => 'reports.productivity_export', 'sort_order' => 19061],

    // SPRINT REPORT
    ['name' => 'reports.sprint_view', 'sort_order' => 19070],
    ['name' => 'reports.sprint_export', 'sort_order' => 19071],

    // MILESTONE REPORT
    ['name' => 'reports.milestone_view', 'sort_order' => 19080],
    ['name' => 'reports.milestone_export', 'sort_order' => 19081],

    // LEAVE REPORT
    ['name' => 'reports.leave_view', 'sort_order' => 19090],
    ['name' => 'reports.leave_export', 'sort_order' => 19091],


    // ACTIVITY LOG (20000)
    ['name' => 'activity_log.view',   'sort_order' => 20000],
    ['name' => 'activity_log.delete', 'sort_order' => 20020],

    // AGILE MILESTONE (21000)
    ['name' => 'agile_milestone.view',   'sort_order' => 21000],
    ['name' => 'agile_milestone.create', 'sort_order' => 21020],
    ['name' => 'agile_milestone.edit',   'sort_order' => 21040],
    ['name' => 'agile_milestone.delete', 'sort_order' => 21060],

    // AGILE SPRINT (22000)
    ['name' => 'agile_sprint.view',   'sort_order' => 22000],
    ['name' => 'agile_sprint.create', 'sort_order' => 22020],
    ['name' => 'agile_sprint.edit',   'sort_order' => 22040],
    ['name' => 'agile_sprint.delete', 'sort_order' => 22060],

    // TASK SETTINGS (23000)
    ['name' => 'task_settings.view',   'sort_order' => 23000],
    ['name' => 'task_settings.create', 'sort_order' => 23020],
    ['name' => 'task_settings.edit',   'sort_order' => 23040],
    ['name' => 'task_settings.delete', 'sort_order' => 23060],

    // TASK TIME LOG CHANGE REQUEST (24000)
    ['name' => 'task_time_log_change_request.approve_reject', 'sort_order' => 24000],

    // KPI (25000)
    ['name' => 'kpi.view',   'sort_order' => 25000],
    ['name' => 'kpi.create', 'sort_order' => 25020],
    ['name' => 'kpi.edit',   'sort_order' => 25040],
    ['name' => 'kpi.delete', 'sort_order' => 25060],

    // Project checklist template (26000)
    ['name' => 'checklist_template.view',   'sort_order' => 26000],
    ['name' => 'checklist_template.create', 'sort_order' => 26020],
    ['name' => 'checklist_template.edit',   'sort_order' => 26040],
    ['name' => 'checklist_template.delete', 'sort_order' => 26060],

    // HANDOFF (27000)
    ['name' => 'handoff_request.view_all',     'sort_order' => 27000],
    ['name' => 'handoff_request.view',         'sort_order' => 27020],
    ['name' => 'handoff_request.create',       'sort_order' => 27040],
    ['name' => 'handoff_request.note',         'sort_order' => 27060],

    // Break Request (28000)
    ['name' => 'break_request.approve_reject',     'sort_order' => 28000],

];
