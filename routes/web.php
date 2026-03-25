<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\IndustryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectCategoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectStatusController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\ScheduleShiftController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TechnologyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // goto dashboard if authenticated, else show welcome page
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
// Route::post('/register', [AuthController::class, 'register'])->name('register.post');

// Reset password Routes
Route::post('/forgot-password', [AuthController::class, 'sendOtp'])->name('forgot.password');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify.otp');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset.password');
// End of Reset password Routes

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    //sample routes for testing dashboard and profile pages, can be removed later
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', function () {
        return view('user-profile');
    })->name('profile');
    // End of sample routes

    // Role & Permission Routes
    Route::patch('/roles/toggle-status', [RolePermissionController::class, 'toggleStatus'])->name('roles.toggleStatus')->middleware('permission.type:role.edit');
    Route::resource('roles', RolePermissionController::class)->middleware('permission.type:role.view')->only(['index']);
    Route::resource('roles', RolePermissionController::class)->middleware('permission.type:role.create')->only(['create', 'store']);
    Route::resource('roles', RolePermissionController::class)->middleware('permission.type:role.edit')->only(['edit', 'update']);
    Route::post('/get-permissions-by-user-type', [RolePermissionController::class, 'getPermissionsByUserType'])->name('roles.permissions.byUserType');
    // End of Role & Permission Routes

    // User Management Routes
    Route::patch('/users/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus')->middleware('permission.type:user.edit');
    Route::resource('users', UserController::class)->middleware('permission.type:user.view')->only(['index']);
    Route::resource('users', UserController::class)->middleware('permission.type:user.create')->only(['create', 'store']);
    Route::resource('users', UserController::class)->middleware('permission.type:user.edit')->only(['edit', 'update']);
    Route::resource('users', UserController::class)->middleware('permission.type:user.delete')->only(['destroy']);
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
    // End Notification Routes

    // Customer Routes
    Route::patch('/customers/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggleStatus')->middleware('permission.type:customer.edit');
    Route::resource('customers', CustomerController::class)->middleware('permission.type:customer.view')->only(['index']);
    Route::resource('customers', CustomerController::class)->middleware('permission.type:customer.create')->only(['create', 'store']);
    Route::resource('customers', CustomerController::class)->middleware('permission.type:customer.edit')->only(['edit', 'update']);
    Route::resource('customers', CustomerController::class)->middleware('permission.type:customer.delete')->only(['destroy']);
    // End Customer Routes

    // Common Routes
    Route::get('/countries/search', [CommonController::class, 'search'])->name('countries.search');
    // End Common Routes

    // Project Routes
    Route::resource('projects', ProjectController::class)->middleware('permission.type:project.view')->only(['index']);
    Route::resource('projects', ProjectController::class)->middleware('permission.type:project.create')->only(['create', 'store']);
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->middleware('permission.type:project.view')->name('projects.edit');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->middleware('permission.type:project.edit')->name('projects.update');
    Route::post('projects/{project}/update-notes', [ProjectController::class, 'updateNotes'])->middleware('permission.type:project.update_notes')->name('projects.updateNotes');
    Route::resource('projects', ProjectController::class)->middleware('permission.type:project.delete')->only(['destroy']);
    // End Project Routes
});
