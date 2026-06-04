<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AgileMilestoneController;
use App\Http\Controllers\AgileSprintController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BreakRequestController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerRestoreController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\HandoffController;
use App\Http\Controllers\IndustryController;
use App\Http\Controllers\KPIController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectCategoryController;
use App\Http\Controllers\ProjectChecklistController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectRestoreController;
use App\Http\Controllers\ProjectMilestoneController;
use App\Http\Controllers\ProjectPaymentController;
use App\Http\Controllers\ProjectSprintController;
use App\Http\Controllers\ProjectStageController;
use App\Http\Controllers\ProjectStatusController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\ScheduleShiftController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskRequestController;
use App\Http\Controllers\TaskSettingsController;
use App\Http\Controllers\TaskTimeLogChangeRequestController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TechnologyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserHierarchyController;
use App\Http\Controllers\UserRestoreController;
use App\Http\Controllers\UserWorkspaceController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    // goto dashboard if authenticated, else show welcome page
    if (auth()->check()) {
        $user = Auth::user();

        if ($user && ($user->is_super_admin || $user->can('dashboard.view'))) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('user.workspace');
    }
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Reset password Routes
Route::post('/forgot-password', [AuthController::class, 'sendOtp'])->name('forgot.password');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify.otp');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset.password');
// End of Reset password Routes

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->middleware('permission.type:dashboard.view')->name('dashboard');
    Route::get('/dashboard/summary', [DashboardController::class, 'summary'])->middleware('permission.type:dashboard.view')->name('dashboard.summary');
    Route::get('/dashboard/worked-time', [DashboardController::class, 'workedTime'])->middleware('permission.type:dashboard.view')->name('dashboard.worked-time');
    Route::get('/dashboard/running-tasks', [DashboardController::class, 'runningTasks'])->middleware('permission.type:dashboard.view')->name('dashboard.running-tasks');
    // End of Dashboard Routes

    // User workspace route
    Route::get('/user-workspace', [UserWorkspaceController::class, 'index'])->name('user.workspace');
    Route::get('/workspace/daily-timeline/refresh', [UserWorkspaceController::class, 'refreshDailyTimeline'])->name('workspace.daily-timeline.refresh');
    Route::get('/break-work-requests', [BreakRequestController::class, 'index'])->name('break-requests.index');
    Route::post('/break-work-requests', [BreakRequestController::class, 'store'])->name('break-work-requests.store');
    Route::match(['put', 'patch'], '/break-work-requests/{breakWorkRequest}', [BreakRequestController::class, 'update'])->name('break-work-requests.update');

    Route::post('/break-work-requests/bulk/{action}', [BreakRequestController::class, 'handleBulkAction'])
        ->middleware(['permission.type:break_request.approve_reject'])
        ->whereIn('action', ['approve', 'reject'])
        ->name('break-requests.bulk-action');

    Route::post('/break-work-requests/{breakWorkRequest}/{action}', [BreakRequestController::class, 'handleAction'])
        ->middleware(['permission.type:break_request.approve_reject'])
        ->whereIn('action', ['approve', 'reject'])
        ->name('break-requests.action');

    Route::prefix('user-analytics')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('user.analytics');
        Route::get('/summary', [AnalyticsController::class, 'summary'])->name('analytics.summary');
        Route::get('/chart/task-status', [AnalyticsController::class, 'taskStatusChart'])->name('analytics.chart.task-status');
        Route::get('/chart/task-priority', [AnalyticsController::class, 'taskPriorityChart'])->name('analytics.chart.task-priority');
        Route::get('/chart/time-comparison', [AnalyticsController::class, 'timeComparisonChart'])->name('analytics.chart.time-comparison');
    });

    // Role & Permission Routes
    Route::patch('/roles/toggle-status', [RolePermissionController::class, 'toggleStatus'])->name('roles.toggleStatus')->middleware('permission.type:role.edit');
    Route::resource('roles', RolePermissionController::class)->middleware('permission.type:role.view')->only(['index']);
    Route::resource('roles', RolePermissionController::class)->middleware('permission.type:role.create')->only(['create', 'store']);
    Route::resource('roles', RolePermissionController::class)->middleware('permission.type:role.edit')->only(['edit', 'update']);
    Route::post('/get-permissions-by-user-type', [RolePermissionController::class, 'getPermissionsByUserType'])->name('roles.permissions.byUserType');
    // End of Role & Permission Routes

    // User Management Routes
    Route::post('/users/notification-settings', [UserController::class, 'updateNotificationSettings'])->name('users.notification.settings');
    Route::post('/users/general-settings', [UserController::class, 'updateGeneralSettings'])->name('users.general.settings');
    Route::post('/users/change-password', [UserController::class, 'changePassword'])->name('users.change.password');

    Route::put('/users/{user}/modal-update', [UserController::class, 'updateModal'])->name('users.modal.update');
    Route::patch('/users/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus')->middleware('permission.type:user.edit');
    Route::resource('users', UserController::class)->middleware('permission.type:user.view')->only(['index']);
    Route::resource('users', UserController::class)->middleware('permission.type:user.create')->only(['create', 'store']);
    Route::resource('users', UserController::class)->only(['show']);
    Route::resource('users', UserController::class)->middleware(['permission.type:user.edit', 'can:update,user'])->only(['edit', 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware(['permission.type:user.delete', 'can:delete,user']);

    // User restore routes
    Route::get('/restore/users', [UserRestoreController::class, 'restoreIndex'])->middleware('permission.type:user.restore')->name('users.restore.index');
    Route::post('/restore/users/bulk', [UserRestoreController::class, 'bulkRestore'])->middleware('permission.type:user.restore')->name('users.restore.bulk');
    Route::post('/restore/users/{id}', [UserRestoreController::class, 'restore'])->middleware('permission.type:user.restore')->name('users.restore');
    // End of User Management Routes

    // Settings Routes
    Route::prefix('settings')->as('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');

        // Department Routes
        Route::patch('/departments/toggle-status', [DepartmentController::class, 'toggleStatus'])->middleware('permission.type:department.edit')->name('department.toggleStatus');
        Route::resource('departments', DepartmentController::class)->middleware('permission.type:department.view')->only(['index']);
        Route::resource('departments', DepartmentController::class)->middleware('permission.type:department.create')->only(['store']);
        Route::resource('departments', DepartmentController::class)->middleware('permission.type:department.edit')->only(['update']);
        Route::resource('departments', DepartmentController::class)->middleware('permission.type:department.delete')->only(['destroy']);

        // Designation Routes
        Route::patch('/designations/toggle-status', [DesignationController::class, 'toggleStatus'])->middleware('permission.type:designation.edit')->name('designation.toggleStatus');
        Route::resource('designations', DesignationController::class)->middleware('permission.type:designation.view')->only(['index']);
        Route::resource('designations', DesignationController::class)->middleware('permission.type:designation.create')->only(['store']);
        Route::resource('designations', DesignationController::class)->middleware('permission.type:designation.edit')->only(['update']);
        Route::resource('designations', DesignationController::class)->middleware('permission.type:designation.delete')->only(['destroy']);

        // Shift Routes
        Route::get('/shifts/{shift}/check-assignment', [ShiftController::class, 'checkAssignment'])->name('shifts.checkAssignment');
        Route::patch('/shifts/toggle-status', [ShiftController::class, 'toggleStatus'])->name('shift.toggleStatus')->middleware('permission.type:shift.edit');
        Route::resource('shifts', ShiftController::class)->middleware('permission.type:shift.view')->only(['index']);
        Route::resource('shifts', ShiftController::class)->middleware('permission.type:shift.create')->only(['create', 'store']);
        Route::resource('shifts', ShiftController::class)->middleware('permission.type:shift.edit')->only(['edit', 'update']);
        Route::resource('shifts', ShiftController::class)->middleware('permission.type:shift.delete')->only(['destroy']);
        // End Shift Routes

        // Technology Routes
        Route::patch('/technologies/toggle-status', [TechnologyController::class, 'toggleStatus'])->middleware('permission.type:technology.edit')->name('technology.toggleStatus');
        Route::resource('technologies', TechnologyController::class)->middleware('permission.type:technology.view')->only(['index']);
        Route::resource('technologies', TechnologyController::class)->middleware('permission.type:technology.create')->only(['store']);
        Route::resource('technologies', TechnologyController::class)->middleware('permission.type:technology.edit')->only(['update']);
        Route::resource('technologies', TechnologyController::class)->middleware('permission.type:technology.delete')->only(['destroy']);
        // End Technology Routes

        // Project Category Routes
        Route::patch('/project-categories/toggle-status', [ProjectCategoryController::class, 'toggleStatus'])->middleware('permission.type:project_category.edit')->name('project_category.toggleStatus');
        Route::resource('project-categories', ProjectCategoryController::class)->middleware('permission.type:project_category.view')->only(['index']);
        Route::resource('project-categories', ProjectCategoryController::class)->middleware('permission.type:project_category.create')->only(['store']);
        Route::resource('project-categories', ProjectCategoryController::class)->middleware('permission.type:project_category.edit')->only(['update']);
        Route::resource('project-categories', ProjectCategoryController::class)->middleware('permission.type:project_category.delete')->only(['destroy']);
        // End Project Category Routes

        // Industry Routes
        Route::patch('/industries/toggle-status', [IndustryController::class, 'toggleStatus'])->middleware('permission.type:industry.edit')->name('industry.toggleStatus');
        Route::resource('industries', IndustryController::class)->middleware('permission.type:industry.view')->only(['index']);
        Route::resource('industries', IndustryController::class)->middleware('permission.type:industry.create')->only(['store']);
        Route::resource('industries', IndustryController::class)->middleware('permission.type:industry.edit')->only(['update']);
        Route::resource('industries', IndustryController::class)->middleware('permission.type:industry.delete')->only(['destroy']);
        // End Industry Routes

        // Project Status Routes
        Route::patch('/project-statuses/toggle-status', [ProjectStatusController::class, 'toggleStatus'])->middleware('permission.type:project_status.edit')->name('project_status.toggleStatus');
        Route::resource('project-statuses', ProjectStatusController::class)->middleware('permission.type:project_status.view')->only(['index']);
        Route::resource('project-statuses', ProjectStatusController::class)->middleware('permission.type:project_status.create')->only(['store']);
        Route::resource('project-statuses', ProjectStatusController::class)->middleware('permission.type:project_status.edit')->only(['update']);
        Route::resource('project-statuses', ProjectStatusController::class)->middleware('permission.type:project_status.delete')->only(['destroy']);
        // End Project Status Routes

        // Project Stage Routes
        Route::patch('/project-stages/toggle-status', [ProjectStageController::class, 'toggleStatus'])->middleware('permission.type:project_stage.edit')->name('project_stage.toggleStatus');
        Route::resource('project-stages', ProjectStageController::class)->middleware('permission.type:project_stage.view')->only(['index']);
        Route::resource('project-stages', ProjectStageController::class)->middleware('permission.type:project_stage.create')->only(['store']);
        Route::resource('project-stages', ProjectStageController::class)->middleware('permission.type:project_stage.edit')->only(['update']);
        Route::resource('project-stages', ProjectStageController::class)->middleware('permission.type:project_stage.delete')->only(['destroy']);
        // End Project Stage Routes

        // Agile milestone Routes
        Route::patch('/agile-milestones/toggle-status', [AgileMilestoneController::class, 'toggleStatus'])->middleware('permission.type:agile_milestone.edit')->name('agile_milestone.toggleStatus');
        Route::resource('agile-milestones', AgileMilestoneController::class)->middleware('permission.type:agile_milestone.view')->only(['index']);
        Route::resource('agile-milestones', AgileMilestoneController::class)->middleware('permission.type:agile_milestone.create')->only(['store']);
        Route::resource('agile-milestones', AgileMilestoneController::class)->middleware('permission.type:agile_milestone.edit')->only(['update']);
        Route::resource('agile-milestones', AgileMilestoneController::class)->middleware('permission.type:agile_milestone.delete')->only(['destroy']);
        // End Agile milestone Routes

        // Agile sprint Routes
        Route::patch('/agile-sprints/toggle-status', [AgileSprintController::class, 'toggleStatus'])->middleware('permission.type:agile_sprint.edit')->name('agile_sprint.toggleStatus');
        Route::resource('agile-sprints', AgileSprintController::class)->middleware('permission.type:agile_sprint.view')->only(['index']);
        Route::resource('agile-sprints', AgileSprintController::class)->middleware('permission.type:agile_sprint.create')->only(['store']);
        Route::resource('agile-sprints', AgileSprintController::class)->middleware('permission.type:agile_sprint.edit')->only(['update']);
        Route::resource('agile-sprints', AgileSprintController::class)->middleware('permission.type:agile_sprint.delete')->only(['destroy']);
        // End Agile sprint Routes

        // Task settings routes
        Route::patch('/task-statuses/toggle-status', [TaskSettingsController::class, 'toggleStatusTaskStatus'])->middleware('permission.type:task_settings.edit')->name('task_status.toggleStatus');
        Route::resource('task-statuses', TaskSettingsController::class)->middleware('permission.type:task_settings.view')->only(['index']);
        Route::resource('task-statuses', TaskSettingsController::class)->middleware('permission.type:task_settings.create')->only(['store']);
        Route::resource('task-statuses', TaskSettingsController::class)->middleware('permission.type:task_settings.edit')->only(['update']);
        Route::resource('task-statuses', TaskSettingsController::class)->middleware('permission.type:task_settings.delete')->only(['destroy']);

        Route::patch('/task-types/toggle-status', [TaskSettingsController::class, 'toggleStatusTaskType'])->middleware('permission.type:task_settings.edit')->name('task_type.toggleStatus');
        Route::resource('task-types', TaskSettingsController::class)->middleware('permission.type:task_settings.view')->only(['index']);
        Route::resource('task-types', TaskSettingsController::class)->middleware('permission.type:task_settings.create')->only(['store']);
        Route::resource('task-types', TaskSettingsController::class)->middleware('permission.type:task_settings.edit')->only(['update']);
        Route::resource('task-types', TaskSettingsController::class)->middleware('permission.type:task_settings.delete')->only(['destroy']);

        Route::patch('/task-modes/toggle-status', [TaskSettingsController::class, 'toggleStatusTaskMode'])->middleware('permission.type:task_settings.edit')->name('task_mode.toggleStatus');
        Route::resource('task-modes', TaskSettingsController::class)->middleware('permission.type:task_settings.view')->only(['index']);
        Route::resource('task-modes', TaskSettingsController::class)->middleware('permission.type:task_settings.create')->only(['store']);
        Route::resource('task-modes', TaskSettingsController::class)->middleware('permission.type:task_settings.edit')->only(['update']);
        Route::resource('task-modes', TaskSettingsController::class)->middleware('permission.type:task_settings.delete')->only(['destroy']);
        // End Task settings Routes

        // Configuration Routes
        Route::get('configurations', [ConfigurationController::class, 'edit'])->middleware('permission.type:configuration.view')->name('configurations.edit');
        Route::put('configurations', [ConfigurationController::class, 'update'])->middleware('permission.type:configuration.edit')->name('configurations.update');
        // End Configuration Routes

        // KPI templates routes
        Route::patch('/kpis/toggle-status', [KPIController::class, 'toggleStatusKPI'])->middleware('permission.type:kpi.edit')->name('kpi.toggleStatus');
        Route::resource('kpis', KPIController::class)->middleware('permission.type:kpi.view')->only(['index']);
        Route::resource('kpis', KPIController::class)->middleware('permission.type:kpi.create')->only(['store']);
        Route::resource('kpis', KPIController::class)->middleware('permission.type:kpi.edit')->only(['update']);
        Route::resource('kpis', KPIController::class)->middleware('permission.type:kpi.delete')->only(['destroy']);
        // End KPI templates routes

        // Checklists templates routes
        Route::patch('/checklists/toggle-status', [ChecklistController::class, 'toggleStatusChecklist'])->middleware('permission.type:checklist_template.edit')->name('checklist.toggleStatus');
        Route::resource('checklists', ChecklistController::class)->middleware('permission.type:checklist_template.view')->only(['index']);
        Route::resource('checklists', ChecklistController::class)->middleware('permission.type:checklist_template.create')->only(['store']);
        Route::resource('checklists', ChecklistController::class)->middleware('permission.type:checklist_template.edit')->only(['update']);
        Route::resource('checklists', ChecklistController::class)->middleware('permission.type:checklist_template.delete')->only(['destroy']);
        // End Checklists templates routes
    });
    // End Settings Routes

    // Team management Routes
    Route::patch('/teams/toggle-status', [TeamController::class, 'toggleStatus'])->name('teams.toggleStatus')->middleware('permission.type:user.edit');
    Route::resource('teams', TeamController::class)->middleware('permission.type:team.view')->only(['index']);
    Route::resource('teams', TeamController::class)->middleware('permission.type:team.create')->only(['create', 'store']);
    Route::resource('teams', TeamController::class)->middleware('permission.type:team.edit')->only(['edit', 'update']);
    Route::resource('teams', TeamController::class)->middleware('permission.type:team.delete')->only(['destroy']);
    // End Team management Routes

    // Schedule shift Routes
    Route::get('schedule-shift', [ScheduleShiftController::class, 'index'])->middleware('permission.type:schedule_shift.view')->name('schedule.shift.index');
    Route::get('create-schedule-shift', [ScheduleShiftController::class, 'create'])->middleware('permission.type:schedule_shift.create')->name('schedule.shift.create');
    Route::post('create-schedule-shift', [ScheduleShiftController::class, 'store'])->middleware('permission.type:schedule_shift.create')->name('schedule.shift.store');
    Route::post('/schedule-shift/update', [ScheduleShiftController::class, 'updateSchedule'])->middleware('permission.type:schedule_shift.edit')->name('schedule.shift.update');
    Route::post('/schedule-shift/preview', [ScheduleShiftController::class, 'preview'])->name('schedule.shift.preview');
    // End Schedule shift Routes

    // Notification Routes
    Route::get('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.markRead');
    // End Notification Routes

    // Customer Routes
    Route::patch('/customers/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggleStatus')->middleware('permission.type:customer.edit');
    Route::resource('customers', CustomerController::class)->middleware('permission.type:customer.view')->only(['index']);
    Route::resource('customers', CustomerController::class)->middleware('permission.type:customer.create')->only(['create', 'store']);
    Route::resource('customers', CustomerController::class)->middleware('permission.type:customer.edit')->only(['edit', 'update']);
    Route::resource('customers', CustomerController::class)->middleware('permission.type:customer.delete')->only(['destroy']);

    // Customer Restore Routes
    Route::get('/restore/customers', [CustomerRestoreController::class, 'restoreIndex'])->middleware('permission.type:customer.restore')->name('customers.restore.index');
    Route::post('/restore/customers/bulk', [CustomerRestoreController::class, 'bulkRestore'])->middleware('permission.type:customer.restore')->name('customers.restore.bulk');
    Route::post('/restore/customers/{id}', [CustomerRestoreController::class, 'restore'])->middleware('permission.type:customer.restore')->name('customers.restore');
    // End Customer Routes

    // Common Routes
    Route::get('/countries/search', [CommonController::class, 'search'])->name('countries.search');
    // End Common Routes

    // Project Routes
    Route::prefix('projects/{project}')->middleware('can:view,project')->group(function () {
        Route::get('tabs/{tab}', [ProjectController::class, 'tab'])->middleware('permission.type:project.view')->name('projects.tabs.show');
        Route::get('delete-summary', [ProjectController::class, 'deleteSummary'])->middleware(['permission.type:project.delete', 'can:delete,project'])->name('projects.delete-summary');

        // Project task routes
        Route::get('tasks/groups', [ProjectTaskController::class, 'taskGroupsPage'])->middleware('permission.type:project.view')->name('projects.tasks.groups.index');
        Route::get('tasks/groups/{group}', [ProjectTaskController::class, 'taskGroup'])->middleware('permission.type:project.view')->name('projects.tasks.groups.show');
        Route::get('tasks/parent-options', [ProjectTaskController::class, 'taskParentOptions'])->middleware('permission.type:project.view')->name('projects.tasks.parent-options');
        Route::get('tasks/{task}/modal', [ProjectTaskController::class, 'taskModal'])->name('projects.tasks.modal');
        Route::post('tasks', [ProjectTaskController::class, 'storeTask'])->middleware('permission.type:task.create')->name('projects.tasks.store');
        Route::put('tasks/{task}', [ProjectTaskController::class, 'updateTask'])->middleware(['permission.type:task.edit'])->name('projects.tasks.update');
        Route::patch('tasks/{task}/move', [ProjectTaskController::class, 'moveTask'])->middleware(['permission.type:task.move', 'can:update,project', 'can:move,task'])->name('projects.tasks.move');
        Route::delete('tasks/{task}', [ProjectTaskController::class, 'destroyTask'])->middleware(['permission.type:task.delete', 'can:update,project', 'can:delete,task'])->name('projects.tasks.destroy');

        // Comments and activity log routes
        Route::get('activity-modal', [ProjectController::class, 'activityModal'])->middleware('permission.type:activity_log.view')->name('projects.activity.modal');
        Route::get('comments-modal', [ProjectController::class, 'commentsModal'])->middleware('permission.type:project.view')->name('projects.comments.modal');
        Route::post('comments', [ProjectController::class, 'storeComment'])->middleware('permission.type:project.view')->name('projects.comments.store');

        // Project notes and attachments routes
        Route::post('notes', [ProjectController::class, 'storeNote'])->middleware('permission.type:project.add_notes_files')->name('projects.storeNote');
        Route::delete('notes/{note}', [ProjectController::class, 'deleteNote'])->middleware('permission.type:project.remove_notes_files')->name('projects.deleteNote');
        Route::delete('notes/{note}/attachments/{attachment}', [ProjectController::class, 'deleteNoteAttachment'])->middleware('permission.type:project.remove_notes_files')->name('projects.deleteNoteAttachment');

        Route::patch('project-status', [ProjectController::class, 'updateProjectStatus'])->middleware(['permission.type:project.status_change', 'can:update,project'])->name('projects.updateProjectStatus');
        Route::patch('project-stage', [ProjectController::class, 'updateProjectStage'])->middleware(['permission.type:project.edit', 'can:update,project'])->name('projects.updateProjectStage');

        // Project payment status route
        Route::match(['patch', 'post'], 'payment-status', [ProjectPaymentController::class, 'addProjectPaymentStatus'])->middleware(['permission.type:project.add_payment_status', 'can:update,project'])->name('projects.addProjectPaymentStatus');
        Route::patch('payments/{payment}', [ProjectPaymentController::class, 'updateProjectPaymentStatus'])->middleware(['permission.type:project.add_payment_status', 'can:update,project'])->name('projects.updateProjectPaymentStatus');

        // Project milestone and sprint routes
        Route::post('milestones', [ProjectMilestoneController::class, 'store'])->middleware(['permission.type:project_milestone.create', 'can:update,project'])->name('projects.milestones.store');
        Route::get('milestones/{projectMilestone}/sprints', [ProjectSprintController::class, 'index'])->middleware('permission.type:project.view')->name('projects.milestones.sprints.index');
        Route::post('milestones/{projectMilestone}/sprints', [ProjectSprintController::class, 'store'])->middleware(['permission.type:project_sprint.create', 'can:update,project'])->name('projects.milestones.sprints.store');
        Route::put('sprints/{projectSprint}', [ProjectSprintController::class, 'update'])->middleware(['permission.type:project_sprint.edit', 'can:update,project'])->name('projects.sprints.update');
        Route::delete('sprints/{projectSprint}', [ProjectSprintController::class, 'destroy'])->middleware(['permission.type:project_sprint.delete', 'can:update,project'])->name('projects.sprints.destroy');
        Route::post('sprints/{projectSprint}/restore', [ProjectSprintController::class, 'restore'])->middleware(['permission.type:project_sprint.delete', 'can:update,project'])->name('projects.sprints.restore');
        Route::patch('milestones/{projectMilestone}/sprints/reorder', [ProjectSprintController::class, 'reorder'])->middleware(['permission.type:project_sprint.edit', 'can:update,project'])->name('projects.milestones.sprints.reorder');
        Route::patch('milestones/reorder', [ProjectMilestoneController::class, 'reorder'])->middleware(['permission.type:project_milestone.edit', 'can:update,project'])->name('projects.milestones.reorder');
        Route::put('milestones/{projectMilestone}', [ProjectMilestoneController::class, 'update'])->middleware(['permission.type:project_milestone.edit', 'can:update,project'])->name('projects.milestones.update');
        Route::delete('milestones/{projectMilestone}', [ProjectMilestoneController::class, 'destroy'])->middleware(['permission.type:project_milestone.delete', 'can:update,project'])->name('projects.milestones.destroy');
        Route::post('milestones/{projectMilestone}/restore', [ProjectMilestoneController::class, 'restore'])->middleware(['permission.type:project_milestone.restore', 'can:update,project'])->name('projects.milestones.restore');

        // Scope file routes
        Route::post('scope-files', [ProjectController::class, 'uploadScopeFile'])->middleware('permission.type:project.add_scope')->name('projects.uploadScopeFile');
        Route::delete('scope-files/{fileId}', [ProjectController::class, 'deleteScopeFile'])->middleware('permission.type:project.remove_scope')->name('projects.deleteScopeFile');

        // Team management within project
        Route::post('members', [ProjectMemberController::class, 'addMember'])->middleware('permission.type:project.add_team')->name('projects.addMember');
        Route::delete('members/{userId}', [ProjectMemberController::class, 'removeMember'])->middleware('permission.type:project.remove_team')->name('projects.removeMember');
        Route::patch('members/{userId}/toggle-status', [ProjectMemberController::class, 'toggleStatus'])->middleware('permission.type:project.remove_team')->name('projects.toggleStatus');
        Route::patch('members/{userId}/role', [ProjectMemberController::class, 'updateRole'])->middleware('permission.type:project.remove_team')->name('projects.updateMemberRole');

        // Assigned checklist routes
        Route::get('members/{userId}/checklists', [ProjectChecklistController::class, 'show'])->middleware('permission.type:project.add_team')->name('projects.checklists.show');
        Route::put('members/{userId}/checklists', [ProjectChecklistController::class, 'update'])->middleware('permission.type:project.add_team')->name('projects.checklists.update');
        Route::post('checklists/render-workspace', [ProjectChecklistController::class, 'renderWorkspaceChecklist'])->middleware('permission.type:project.add_team')->name('projects.checklists.renderWorkspace');
        Route::post('checklists/render-library', [ProjectChecklistController::class, 'renderLibraryChecklist'])->middleware('permission.type:project.add_team')->name('projects.checklists.renderLibrary');
        Route::patch('checklists/items/{itemId}/toggle', [ProjectChecklistController::class, 'toggleItemStatus'])->middleware('permission.type:project.view')->name('projects.checklists.toggleItem');
    });

    // Project Restore Routes
    Route::get('projects/restore', [ProjectRestoreController::class, 'index'])->middleware('permission.type:project.restore')->name('projects.restore.index');
    Route::post('projects/restore/bulk', [ProjectRestoreController::class, 'bulkRestore'])->middleware('permission.type:project.restore')->name('projects.restore.bulk');
    Route::post('projects/restore/{id}', [ProjectRestoreController::class, 'restore'])->middleware('permission.type:project.restore')->name('projects.restore');
    Route::prefix('projects/restore/{id}')->middleware('permission.type:project.restore')->group(function () {
        Route::get('view', [ProjectRestoreController::class, 'show'])->name('projects.restore.show');
        Route::get('tabs/{tab}', [ProjectRestoreController::class, 'tab'])->name('projects.restore.tabs.show');
        Route::get('activity-modal', [ProjectRestoreController::class, 'activityModal'])->middleware('permission.type:activity_log.view')->name('projects.restore.activity.modal');
        Route::get('comments-modal', [ProjectRestoreController::class, 'commentsModal'])->name('projects.restore.comments.modal');
        Route::get('milestones/{projectMilestone}/sprints', [ProjectRestoreController::class, 'milestoneSprints'])->name('projects.restore.milestones.sprints.index');
        Route::get('tasks/groups', [ProjectRestoreController::class, 'taskGroupsPage'])->name('projects.restore.tasks.groups.index');
        Route::get('tasks/groups/{group}', [ProjectRestoreController::class, 'taskGroup'])->name('projects.restore.tasks.groups.show');
    });

    Route::resource('projects', ProjectController::class)->middleware(['permission.type:project.view'])->only(['index']);
    Route::resource('projects', ProjectController::class)->middleware(['permission.type:project.create'])->only(['create', 'store']);
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->middleware(['permission.type:project.view', 'can:view,project'])->name('projects.edit');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->middleware(['permission.type:project.edit', 'can:update,project'])->name('projects.update');
    Route::resource('projects', ProjectController::class)->middleware(['permission.type:project.delete', 'can:delete,project'])->only(['destroy']);
    // End Project Routes

    // Task Routes
    Route::get('tasks/quick-create/parent-options', [TaskController::class, 'quickCreateParentOptions'])->name('tasks.quick-create-parent-options');

    Route::prefix('tasks/{task}')->as('tasks.')->group(function () {
        Route::get('tabs/{tab}', [TaskController::class, 'tab'])->middleware(['permission.type:task.view', 'can:view,task'])->name('tabs.show');
        Route::get('parent-options', [TaskController::class, 'parentTaskOptions'])->middleware(['permission.type:task.view', 'can:view,task'])->name('parent-options');
        Route::patch('overview/description', [TaskController::class, 'updateOverviewDescription'])->middleware(['permission.type:task.edit', 'can:update,task'])->name('overview.description.update');

        Route::get('activity-modal', [TaskController::class, 'activityModal'])->middleware(['permission.type:activity_log.view', 'can:view,task'])->name('activity.modal');
        Route::get('comments-modal', [TaskController::class, 'commentsModal'])->middleware(['permission.type:task.view', 'can:view,task'])->name('comments.modal');
        Route::post('comments', [TaskController::class, 'storeComment'])->middleware(['permission.type:task.view', 'can:view,task'])->name('comments.store');

        // Task notes and attachments routes
        Route::post('notes', [TaskController::class, 'storeNote'])->middleware(['permission.type:task.add_notes_files'])->name('notes.store');
        Route::delete('notes/{note}', [TaskController::class, 'deleteNote'])->middleware(['permission.type:task.remove_notes_files'])->name('notes.delete');
        Route::delete('notes/{note}/attachments/{attachment}', [TaskController::class, 'deleteNoteAttachment'])->middleware(['permission.type:task.remove_notes_files'])->name('notes.attachments.delete');

        // Task timer routes
        Route::post('/start', [TaskController::class, 'start'])->name('start');
        Route::post('/stop', [TaskController::class, 'stop'])->name('stop');

        Route::get('edit', [TaskController::class, 'edit'])->middleware(['permission.type:task.view', 'can:view,task'])->name('edit');
    });

    Route::resource('tasks', TaskController::class)->middleware(['permission.type:task.view'])->only(['index']);
    Route::resource('tasks', TaskController::class)->middleware(['permission.type:task.create'])->only(['create', 'store']);
    Route::resource('tasks', TaskController::class)->middleware(['permission.type:task.delete', 'can:delete,task'])->only(['destroy']);
    Route::get('tasks/kanban-view', [TaskController::class, 'kanbanView'])->middleware(['permission.type:task.view'])->name('tasks.kanban.view');
    Route::get('tasks/dropdown-options', [TaskController::class, 'dropdownOptions'])->name('tasks.dropdown-options');

    // Task status, order change route
    Route::get('/tasks/kanban', [TaskController::class, 'kanbanMode'])->name('tasks.kanbanMode');
    Route::patch('/tasks/transition-status', [TaskController::class, 'transitionStatus'])->name('tasks.transition-status');
    // End Task Routes

    // Task request routes
    Route::post('tasks/request', [TaskController::class, 'store'])->name('tasks.request.store');
    Route::get('tasks/requests', [TaskRequestController::class, 'index'])->name('tasks.requests.index');
    Route::post('tasks/requests/bulk/{action}', [TaskRequestController::class, 'handleBulkAction'])
        ->whereIn('action', ['approve', 'reject'])
        ->name('tasks.requests.bulk-action');
    Route::post('tasks/{task}/requests/{action}', [TaskRequestController::class, 'handleAction'])
        ->whereIn('action', ['approve', 'reject'])
        ->name('tasks.requests.action');
    // End Task request routes

    // Task time log change request routes
    Route::post('tasks/time-logs/change-requests', [TaskTimeLogChangeRequestController::class, 'store'])->name('tasks.time-log-change-requests.store');
    Route::get('tasks/time-logs/change-requests', [TaskTimeLogChangeRequestController::class, 'index'])->middleware(['permission.type:task_time_log_change_request.approve_reject'])->name('tasks.time-log-change-requests.index');
    Route::post('tasks/time-logs/change-requests/bulk/{action}', [TaskTimeLogChangeRequestController::class, 'handleBulkAction'])->middleware(['permission.type:task_time_log_change_request.approve_reject'])
        ->whereIn('action', ['approve', 'reject'])
        ->name('tasks.time-log-change-requests.bulk-action');
    Route::post('tasks/time-logs/change-requests/{changeRequest}/{action}', [TaskTimeLogChangeRequestController::class, 'handleAction'])->middleware(['permission.type:task_time_log_change_request.approve_reject'])
        ->whereIn('action', ['approve', 'reject'])
        ->name('tasks.time-log-change-requests.action');
    // End Task time log change request routes

    // Handoff Request routes
    Route::prefix('handoff-requests')->as('handoff_requests.')->group(function () {
        Route::get('/', [HandoffController::class, 'index'])->middleware(['permission.type:handoff_request.view|handoff_request.view_all'])->name('index');
        Route::post('/', [HandoffController::class, 'store'])->middleware(['permission.type:handoff_request.create'])->name('store');
        Route::patch('{handoff_request}/assign', [HandoffController::class, 'assign'])->middleware(['permission.type:task.create'])->name('assign');
        Route::patch('{handoff_request}/noted', [HandoffController::class, 'noted'])->middleware(['permission.type:handoff_request.note'])->name('note');
    });
    // End Handoff Request routes

    // Activity Log Route
    Route::get('activity-log', [ActivityLogController::class, 'activityLog'])->middleware('permission.type:activity_log.view')->name('activity.log');
    Route::get('activity-log/{activity}/details', [ActivityLogController::class, 'details'])->name('activity.log.details');
    Route::delete('activity-log/bulk-delete', [ActivityLogController::class, 'bulkDelete'])->middleware('permission.type:activity_log.delete')->name('activity.log.bulkDelete');
    Route::delete('activity-log/{activity}', [ActivityLogController::class, 'destroy'])->middleware('permission.type:activity_log.delete')->name('activity.log.destroy');

    // User hierarchy tree view route
    Route::get('user-tree-view', [UserHierarchyController::class, 'index'])->middleware('permission.type:user.tree_view')->name('user.tree_view');

    Route::prefix('reports')->as('reports.')->group(function () {

        // To get choosed projects by flow in report filters
        Route::get('/projects/by-flow', [ReportController::class, 'getProjectsByFlow'])->name('projects.by-flow');

        // TIME TRACKING::PERFORMANCE REPORT
        Route::get('/time-tracking-report', [ReportController::class, 'timeTracking'])->middleware('permission.type:reports.time_tracking_view')->name('time_tracking');
        Route::get('/reports/time-tracking/export', [ReportController::class, 'timeTrackingExport'])->middleware('permission.type:reports.time_tracking_export')->name('time_tracking.export');

        // DAILY TIME::PERFORMANCE REPORT
        Route::get('/daily-time-report', [ReportController::class, 'dailyTime'])->middleware('permission.type:reports.daily_time_view')->name('daily_time');
        Route::get('/reports/daily-time/export', [ReportController::class, 'dailyTimeExport'])->middleware('permission.type:reports.daily_time_export')->name('daily_time.export');

        // PROJECT:: PROJECT REPORT
        Route::get('/projects-report', [ReportController::class, 'project'])->middleware('permission.type:reports.project_view')->name('projects');
        Route::get('/reports/project/export', [ReportController::class, 'projectExport'])->middleware('permission.type:reports.project_export')->name('project.export');

        // MILESTONE:: PROJECT REPORT
        Route::get('/milestones-report', [ReportController::class, 'milestone'])->middleware('permission.type:reports.milestone_view')->name('milestones');
        Route::get('/reports/milestones/export', [ReportController::class, 'milestoneExport'])->middleware('permission.type:reports.milestone_export')->name('milestone.export');

        // SPRINT:: PROJECT REPORT
        Route::get('/sprints-report', [ReportController::class, 'sprint'])->middleware('permission.type:reports.sprint_view')->name('sprints');
        Route::get('/reports/sprints/export', [ReportController::class, 'sprintExport'])->middleware('permission.type:reports.sprint_export')->name('sprint.export');

        // TASK:: PROJECT REPORT
        Route::get('/tasks-report', [ReportController::class, 'task'])->middleware('permission.type:reports.task_view')->name('tasks');
        Route::get('/reports/tasks/export', [ReportController::class, 'taskExport'])->middleware('permission.type:reports.task_export')->name('task.export');        
    });
});

Route::get('api-test', function () {
    dd('test for api');
})->name('api-test');
