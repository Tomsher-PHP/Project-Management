<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    //goto dashboard if authenticated, else show welcome page
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    //sample routes for testing dashboard and profile pages, can be removed later
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/dashboard11', function () {
        return view('dashboard11');
    })->name('dashboard11');

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

    Route::resource('user', UserController::class)->middleware('permission.type:user.delete')->only(['destroy']);
    // End of User Management Routes

    // Settings Routes
    Route::prefix('settings')->as('settings.')->group(function() {
        Route::get('/', [SettingsController::class, 'index'])->name('index');

        Route::patch('/department/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('department.toggleStatus');
        Route::resource('departments', DepartmentController::class);

        Route::patch('/designation/toggle-status', [DesignationController::class, 'toggleStatus'])->name('designation.toggleStatus');
        Route::resource('designations', DesignationController::class);
    });
    // End Settings Routes
});
