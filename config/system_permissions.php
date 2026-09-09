<?php

return [

    // DASHBOARD
    ['name' => 'dashboard.view',   'label' => 'Dashboard', 'sort_order' => 500, 'default_checked' => false],

    // USER
    ['name' => 'user.view_all_users', 'label' => 'User', 'sort_order' => 1000, 'default_checked' => false],
    ['name' => 'user.view',           'label' => 'User', 'sort_order' => 1020, 'default_checked' => true],
    ['name' => 'user.create',         'label' => 'User', 'sort_order' => 1040, 'default_checked' => false],
    ['name' => 'user.edit',           'label' => 'User', 'sort_order' => 1060, 'default_checked' => false],
    ['name' => 'user.delete',         'label' => 'User', 'sort_order' => 1080, 'default_checked' => false],
    ['name' => 'user.restore',        'label' => 'User', 'sort_order' => 1100, 'default_checked' => false],
    ['name' => 'user.tree_view',      'label' => 'User', 'sort_order' => 1120, 'default_checked' => true],
    ['name' => 'user.leave_details.view',  'label' => 'Leave Details',    'sort_order' => 1130, 'default_checked' => true],
    ['name' => 'user.leave_details.create', 'label' => 'Leave Details', 'sort_order' => 1140, 'default_checked' => true],
    ['name' => 'user.leave_details.edit', 'label' => 'Leave Details', 'sort_order' => 1150, 'default_checked' => true],
    ['name' => 'user_leave_balance.import',  'label' => 'Leave Details', 'sort_order' => 1160, 'default_checked' => true,],

    // TEAM
    ['name' => 'team.view_all_teams', 'label' => 'Team', 'sort_order' => 2000, 'default_checked' => false],
    ['name' => 'team.view',           'label' => 'Team', 'sort_order' => 2020, 'default_checked' => true],
    ['name' => 'team.create',         'label' => 'Team', 'sort_order' => 2040, 'default_checked' => false],
    ['name' => 'team.edit',           'label' => 'Team', 'sort_order' => 2060, 'default_checked' => false],
    ['name' => 'team.delete',         'label' => 'Team', 'sort_order' => 2080, 'default_checked' => false],

    // CUSTOMER
    ['name' => 'customer.view',    'label' => 'Customer', 'sort_order' => 3000, 'default_checked' => false],
    ['name' => 'customer.create',  'label' => 'Customer', 'sort_order' => 3020, 'default_checked' => false],
    ['name' => 'customer.edit',    'label' => 'Customer', 'sort_order' => 3040, 'default_checked' => false],
    ['name' => 'customer.delete',  'label' => 'Customer', 'sort_order' => 3060, 'default_checked' => false],
    ['name' => 'customer.restore', 'label' => 'Customer', 'sort_order' => 3080, 'default_checked' => false],

    // PROJECT
    ['name' => 'project.view_all_projects',     'label' => 'Project', 'sort_order' => 4000, 'default_checked' => false],
    ['name' => 'project.view',                  'label' => 'Project', 'sort_order' => 4020, 'default_checked' => true],
    ['name' => 'project.create',                'label' => 'Project', 'sort_order' => 4040, 'default_checked' => false],
    ['name' => 'project.edit',                  'label' => 'Project', 'sort_order' => 4060, 'default_checked' => false],
    ['name' => 'project.delete',                'label' => 'Project', 'sort_order' => 4080, 'default_checked' => false],
    ['name' => 'project.restore',               'label' => 'Project', 'sort_order' => 4100, 'default_checked' => false],
    ['name' => 'project.add_team',              'label' => 'Project', 'sort_order' => 4120, 'default_checked' => false],
    ['name' => 'project.remove_team',           'label' => 'Project', 'sort_order' => 4140, 'default_checked' => false],
    ['name' => 'project.add_scope',             'label' => 'Project', 'sort_order' => 4160, 'default_checked' => false],
    ['name' => 'project.remove_scope',          'label' => 'Project', 'sort_order' => 4180, 'default_checked' => false],
    ['name' => 'project.add_notes_files',       'label' => 'Project', 'sort_order' => 4200, 'default_checked' => false],
    ['name' => 'project.remove_notes_files',    'label' => 'Project', 'sort_order' => 4220, 'default_checked' => false],
    ['name' => 'project.status_change',         'label' => 'Project', 'sort_order' => 4240, 'default_checked' => false],
    ['name' => 'project.customer_end_date',     'label' => 'Project', 'sort_order' => 4260, 'default_checked' => false],
    ['name' => 'project.add_payment_status',    'label' => 'Project', 'sort_order' => 4280, 'default_checked' => false],
    ['name' => 'project.view_payment_status',   'label' => 'Project', 'sort_order' => 4290, 'default_checked' => false],

    // PROJECT MILESTONE
    ['name' => 'project_milestone.view',    'label' => 'Project Milestone', 'sort_order' => 5000, 'default_checked' => true],
    ['name' => 'project_milestone.create',  'label' => 'Project Milestone', 'sort_order' => 5020, 'default_checked' => false],
    ['name' => 'project_milestone.edit',    'label' => 'Project Milestone', 'sort_order' => 5040, 'default_checked' => false],
    ['name' => 'project_milestone.delete',  'label' => 'Project Milestone', 'sort_order' => 5060, 'default_checked' => false],
    ['name' => 'project_milestone.restore', 'label' => 'Project Milestone', 'sort_order' => 5080, 'default_checked' => false],

    // PROJECT SPRINT
    ['name' => 'project_sprint.view',    'label' => 'Project Sprint', 'sort_order' => 6000, 'default_checked' => true],
    ['name' => 'project_sprint.create',  'label' => 'Project Sprint', 'sort_order' => 6020, 'default_checked' => false],
    ['name' => 'project_sprint.edit',    'label' => 'Project Sprint', 'sort_order' => 6040, 'default_checked' => false],
    ['name' => 'project_sprint.delete',  'label' => 'Project Sprint', 'sort_order' => 6060, 'default_checked' => false],
    ['name' => 'project_sprint.restore', 'label' => 'Project Sprint', 'sort_order' => 6080, 'default_checked' => false],

    // TASK
    ['name' => 'task.view_all_tasks',     'label' => 'Task', 'sort_order' => 7000, 'default_checked' => false],
    ['name' => 'task.view',               'label' => 'Task', 'sort_order' => 7020, 'default_checked' => true],
    ['name' => 'task.create',             'label' => 'Task', 'sort_order' => 7040, 'default_checked' => false],
    ['name' => 'task.edit',               'label' => 'Task', 'sort_order' => 7060, 'default_checked' => false],
    ['name' => 'task.delete',             'label' => 'Task', 'sort_order' => 7080, 'default_checked' => false],
    ['name' => 'task.add_notes_files',    'label' => 'Task', 'sort_order' => 7120, 'default_checked' => false],
    ['name' => 'task.remove_notes_files', 'label' => 'Task', 'sort_order' => 7140, 'default_checked' => false],
    ['name' => 'task.move',               'label' => 'Task', 'sort_order' => 7160, 'default_checked' => false],

    // SCHEDULE SHIFT
    ['name' => 'schedule_shift.view',   'label' => 'Schedule Shift', 'sort_order' => 8000, 'default_checked' => false],
    ['name' => 'schedule_shift.create', 'label' => 'Schedule Shift', 'sort_order' => 8020, 'default_checked' => false],
    ['name' => 'schedule_shift.edit',   'label' => 'Schedule Shift', 'sort_order' => 8040, 'default_checked' => false],
    ['name' => 'schedule_shift.delete', 'label' => 'Schedule Shift', 'sort_order' => 8060, 'default_checked' => false],

    // ACTIVITY LOG
    ['name' => 'activity_log.view',   'label' => 'Activity Log', 'sort_order' => 9000, 'default_checked' => false],
    ['name' => 'activity_log.delete', 'label' => 'Activity Log', 'sort_order' => 9020, 'default_checked' => false],

    // Appraisal operational
    ['name' => 'appraisal.view',   'label' => 'Appraisal', 'sort_order' => 10000, 'default_checked' => true],
    ['name' => 'appraisal.create', 'label' => 'Appraisal', 'sort_order' => 10020, 'default_checked' => false],
    ['name' => 'appraisal.edit',   'label' => 'Appraisal', 'sort_order' => 10040, 'default_checked' => false],
    ['name' => 'appraisal.delete', 'label' => 'Appraisal', 'sort_order' => 10060, 'default_checked' => false],

    // LEAVE REQUEST
    ['name' => 'leave_request.view', 'label' => 'Leave Request', 'sort_order' => 11000, 'default_checked' => true],
    ['name' => 'leave_request.create', 'label' => 'Leave Request', 'sort_order' => 11020, 'default_checked' => true],
    ['name' => 'leave_request.edit', 'label' => 'Leave Request', 'sort_order' => 11030, 'default_checked' => true],
    ['name' => 'leave_request.delete', 'label' => 'Leave Request', 'sort_order' => 11040, 'default_checked' => false],
    ['name' => 'leave_request.approve', 'label' => 'Leave Request', 'sort_order' => 11050, 'default_checked' => true],
    ['name' => 'leave_request.reject', 'label' => 'Leave Request', 'sort_order' => 11070, 'default_checked' => true],
    ['name' => 'leave_request.cancel', 'label' => 'Leave Request', 'sort_order' => 11090, 'default_checked' => true],

    // ATTENDANCE
    ['name' => 'attendance.view', 'label' => 'Attendance', 'sort_order' => 12000, 'default_checked' => true],
    ['name' => 'attendance.create', 'label' => 'Attendance', 'sort_order' => 12020, 'default_checked' => true],
    ['name' => 'attendance.edit', 'label' => 'Attendance', 'sort_order' => 12040, 'default_checked' => true],
    ['name' => 'attendance.delete', 'label' => 'Attendance', 'sort_order' => 12060, 'default_checked' => false],

    //======================================================================
    // Requests Actions Start
    //======================================================================

    // TASK TIME LOG CHANGE REQUEST 
    ['name' => 'task_time_log_change_request.approve_reject', 'label' => 'Requests - Task Time Log Change', 'sort_order' => 20000, 'default_checked' => false],

    // HANDOFF 
    ['name' => 'handoff_request.view_all',     'label' => 'Requests - Handoff', 'sort_order' => 21000, 'default_checked' => false],
    ['name' => 'handoff_request.view',         'label' => 'Requests - Handoff', 'sort_order' => 21020, 'default_checked' => false],
    ['name' => 'handoff_request.create',       'label' => 'Requests - Handoff', 'sort_order' => 21040, 'default_checked' => false],
    ['name' => 'handoff_request.note',         'label' => 'Requests - Handoff', 'sort_order' => 21060, 'default_checked' => false],

    // Break Request 
    ['name' => 'break_request.approve_reject', 'label' => 'Requests - Break', 'sort_order' => 22000, 'default_checked' => false],

    // Task Time Extend Request 
    ['name' => 'task_time_extend_request.approve_reject', 'label' => 'Requests - Task Time Extend', 'sort_order' => 23000, 'default_checked' => false],


    //======================================================================
    // Reports Modules Start
    //======================================================================

    // TIME TRACKING REPORT
    ['name' => 'reports.time_tracking_view',   'label' => 'Reports - Time Tracking', 'sort_order' => 30000, 'default_checked' => true],
    ['name' => 'reports.time_tracking_export', 'label' => 'Reports - Time Tracking', 'sort_order' => 30020, 'default_checked' => false],

    // DAILY TIME REPORT
    ['name' => 'reports.daily_time_view',   'label' => 'Reports - Daily Time', 'sort_order' => 31000, 'default_checked' => true],
    ['name' => 'reports.daily_time_export', 'label' => 'Reports - Daily Time', 'sort_order' => 31020, 'default_checked' => false],

    // PRODUCTIVITY REPORT
    ['name' => 'reports.productivity_view',   'label' => 'Reports - Productivity', 'sort_order' => 32000, 'default_checked' => true],
    ['name' => 'reports.productivity_export', 'label' => 'Reports - Productivity', 'sort_order' => 32020, 'default_checked' => false],

    // PROJECT REPORT
    ['name' => 'reports.project_view',   'label' => 'Reports - Project', 'sort_order' => 33000, 'default_checked' => false],
    ['name' => 'reports.project_export', 'label' => 'Reports - Project', 'sort_order' => 33020, 'default_checked' => false],

    // MILESTONE REPORT
    ['name' => 'reports.milestone_view',   'label' => 'Reports - Milestone', 'sort_order' => 34000, 'default_checked' => false],
    ['name' => 'reports.milestone_export', 'label' => 'Reports - Milestone', 'sort_order' => 34020, 'default_checked' => false],

    // SPRINT REPORT
    ['name' => 'reports.sprint_view',   'label' => 'Reports - Sprint', 'sort_order' => 35000, 'default_checked' => false],
    ['name' => 'reports.sprint_export', 'label' => 'Reports - Sprint', 'sort_order' => 35020, 'default_checked' => false],

    // TASK REPORT
    ['name' => 'reports.task_view',   'label' => 'Reports - Task', 'sort_order' => 36000, 'default_checked' => false],
    ['name' => 'reports.task_export', 'label' => 'Reports - Task', 'sort_order' => 36020, 'default_checked' => false],

    //======================================================================
    // Settings Modules Start
    //======================================================================

    // ROLE 
    ['name' => 'role.view',   'label' => 'Settings - Role', 'sort_order' => 50000, 'default_checked' => false],
    ['name' => 'role.create', 'label' => 'Settings - Role', 'sort_order' => 50020, 'default_checked' => false],
    ['name' => 'role.edit',   'label' => 'Settings - Role', 'sort_order' => 50040, 'default_checked' => false],
    ['name' => 'role.delete', 'label' => 'Settings - Role', 'sort_order' => 50060, 'default_checked' => false],

    // SHIFT
    ['name' => 'shift.view',   'label' => 'Settings - Shift', 'sort_order' => 51000, 'default_checked' => false],
    ['name' => 'shift.create', 'label' => 'Settings - Shift', 'sort_order' => 51020, 'default_checked' => false],
    ['name' => 'shift.edit',   'label' => 'Settings - Shift', 'sort_order' => 51040, 'default_checked' => false],
    ['name' => 'shift.delete', 'label' => 'Settings - Shift', 'sort_order' => 51060, 'default_checked' => false],

    // DEPARTMENT
    ['name' => 'department.view',   'label' => 'Settings - Department', 'sort_order' => 52000, 'default_checked' => false],
    ['name' => 'department.create', 'label' => 'Settings - Department', 'sort_order' => 52020, 'default_checked' => false],
    ['name' => 'department.edit',   'label' => 'Settings - Department', 'sort_order' => 52040, 'default_checked' => false],
    ['name' => 'department.delete', 'label' => 'Settings - Department', 'sort_order' => 52060, 'default_checked' => false],

    // DESIGNATION
    ['name' => 'designation.view',   'label' => 'Settings - Designation', 'sort_order' => 53000, 'default_checked' => false],
    ['name' => 'designation.create', 'label' => 'Settings - Designation', 'sort_order' => 53020, 'default_checked' => false],
    ['name' => 'designation.edit',   'label' => 'Settings - Designation', 'sort_order' => 53040, 'default_checked' => false],
    ['name' => 'designation.delete', 'label' => 'Settings - Designation', 'sort_order' => 53060, 'default_checked' => false],

    // TECHNOLOGY
    ['name' => 'technology.view',   'label' => 'Settings - Technology', 'sort_order' => 54000, 'default_checked' => false],
    ['name' => 'technology.create', 'label' => 'Settings - Technology', 'sort_order' => 54020, 'default_checked' => false],
    ['name' => 'technology.edit',   'label' => 'Settings - Technology', 'sort_order' => 54040, 'default_checked' => false],
    ['name' => 'technology.delete', 'label' => 'Settings - Technology', 'sort_order' => 54060, 'default_checked' => false],

    // PROJECT CATEGORY
    ['name' => 'project_category.view',   'label' => 'Settings - Project Category', 'sort_order' => 55000, 'default_checked' => false],
    ['name' => 'project_category.create', 'label' => 'Settings - Project Category', 'sort_order' => 55020, 'default_checked' => false],
    ['name' => 'project_category.edit',   'label' => 'Settings - Project Category', 'sort_order' => 55040, 'default_checked' => false],
    ['name' => 'project_category.delete', 'label' => 'Settings - Project Category', 'sort_order' => 55060, 'default_checked' => false],

    // INDUSTRY
    ['name' => 'industry.view',   'label' => 'Settings - Industry', 'sort_order' => 56000, 'default_checked' => false],
    ['name' => 'industry.create', 'label' => 'Settings - Industry', 'sort_order' => 56020, 'default_checked' => false],
    ['name' => 'industry.edit',   'label' => 'Settings - Industry', 'sort_order' => 56040, 'default_checked' => false],
    ['name' => 'industry.delete', 'label' => 'Settings - Industry', 'sort_order' => 56060, 'default_checked' => false],

    // PROJECT STATUS
    ['name' => 'project_status.view',   'label' => 'Settings - Project Status', 'sort_order' => 57000, 'default_checked' => false],
    ['name' => 'project_status.create', 'label' => 'Settings - Project Status', 'sort_order' => 57020, 'default_checked' => false],
    ['name' => 'project_status.edit',   'label' => 'Settings - Project Status', 'sort_order' => 57040, 'default_checked' => false],
    ['name' => 'project_status.delete', 'label' => 'Settings - Project Status', 'sort_order' => 57060, 'default_checked' => false],

    // PROJECT STAGE
    ['name' => 'project_stage.view',   'label' => 'Settings - Project Stage', 'sort_order' => 58000, 'default_checked' => false],
    ['name' => 'project_stage.create', 'label' => 'Settings - Project Stage', 'sort_order' => 58020, 'default_checked' => false],
    ['name' => 'project_stage.edit',   'label' => 'Settings - Project Stage', 'sort_order' => 58040, 'default_checked' => false],
    ['name' => 'project_stage.delete', 'label' => 'Settings - Project Stage', 'sort_order' => 58060, 'default_checked' => false],

    // AGILE MILESTONE
    ['name' => 'agile_milestone.view',   'label' => 'Settings - Project Agile Flow', 'sort_order' => 59000, 'default_checked' => false],
    ['name' => 'agile_milestone.create', 'label' => 'Settings - Project Agile Flow', 'sort_order' => 59020, 'default_checked' => false],
    ['name' => 'agile_milestone.edit',   'label' => 'Settings - Project Agile Flow', 'sort_order' => 59040, 'default_checked' => false],
    ['name' => 'agile_milestone.delete', 'label' => 'Settings - Project Agile Flow', 'sort_order' => 59060, 'default_checked' => false],

    // AGILE SPRINT
    ['name' => 'agile_sprint.view',   'label' => 'Settings - Project Agile Flow', 'sort_order' => 60000, 'default_checked' => false],
    ['name' => 'agile_sprint.create', 'label' => 'Settings - Project Agile Flow', 'sort_order' => 60020, 'default_checked' => false],
    ['name' => 'agile_sprint.edit',   'label' => 'Settings - Project Agile Flow', 'sort_order' => 60040, 'default_checked' => false],
    ['name' => 'agile_sprint.delete', 'label' => 'Settings - Project Agile Flow', 'sort_order' => 60060, 'default_checked' => false],

    // TASK SETTINGS 
    ['name' => 'task_settings.view',   'label' => 'Settings - Task Settings', 'sort_order' => 61000, 'default_checked' => false],
    ['name' => 'task_settings.create', 'label' => 'Settings - Task Settings', 'sort_order' => 61020, 'default_checked' => false],
    ['name' => 'task_settings.edit',   'label' => 'Settings - Task Settings', 'sort_order' => 61040, 'default_checked' => false],
    ['name' => 'task_settings.delete', 'label' => 'Settings - Task Settings', 'sort_order' => 61060, 'default_checked' => false],

    // KPI
    ['name' => 'kpi.view',   'label' => 'Settings - KPI', 'sort_order' => 62000, 'default_checked' => false],
    ['name' => 'kpi.create', 'label' => 'Settings - KPI', 'sort_order' => 62020, 'default_checked' => false],
    ['name' => 'kpi.edit',   'label' => 'Settings - KPI', 'sort_order' => 62040, 'default_checked' => false],
    ['name' => 'kpi.delete', 'label' => 'Settings - KPI', 'sort_order' => 62060, 'default_checked' => false],

    // Project checklist template
    ['name' => 'checklist_template.view',   'label' => 'Settings - Checklist Template', 'sort_order' => 63000, 'default_checked' => false],
    ['name' => 'checklist_template.create', 'label' => 'Settings - Checklist Template', 'sort_order' => 63020, 'default_checked' => false],
    ['name' => 'checklist_template.edit',   'label' => 'Settings - Checklist Template', 'sort_order' => 63040, 'default_checked' => false],
    ['name' => 'checklist_template.delete', 'label' => 'Settings - Checklist Template', 'sort_order' => 63060, 'default_checked' => false],

    // Appraisal Settings
    ['name' => 'appraisal_settings.view',   'label' => 'Settings - Appraisal', 'sort_order' => 64000, 'default_checked' => false],
    ['name' => 'appraisal_settings.create', 'label' => 'Settings - Appraisal', 'sort_order' => 64020, 'default_checked' => false],
    ['name' => 'appraisal_settings.edit',   'label' => 'Settings - Appraisal', 'sort_order' => 64040, 'default_checked' => false],
    ['name' => 'appraisal_settings.delete', 'label' => 'Settings - Appraisal', 'sort_order' => 64060, 'default_checked' => false],

    // CONFIGURATION
    ['name' => 'configuration.view', 'label' => 'Settings - Configuration', 'sort_order' => 65000, 'default_checked' => false],
    ['name' => 'configuration.edit', 'label' => 'Settings - Configuration', 'sort_order' => 65020, 'default_checked' => false],

    // LEAVE TYPES
    ['name' => 'leave_types.view',   'label' => 'Settings - Leave Types', 'sort_order' => 68000, 'default_checked' => false],
    ['name' => 'leave_types.create', 'label' => 'Settings - Leave Types', 'sort_order' => 68020, 'default_checked' => false],
    ['name' => 'leave_types.edit',   'label' => 'Settings - Leave Types', 'sort_order' => 68040, 'default_checked' => false],
    ['name' => 'leave_types.delete', 'label' => 'Settings - Leave Types', 'sort_order' => 68060, 'default_checked' => false],

];
