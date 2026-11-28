<?php

use App\Http\Controllers\CustomerPortal\Auth\ForgotPasswordController;
use App\Http\Controllers\CustomerPortal\Auth\LoginController;
use App\Http\Controllers\CustomerPortal\Auth\OtpVerificationController;
use App\Http\Controllers\CustomerPortal\Auth\ResetPasswordController;
use App\Http\Controllers\CustomerPortal\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Portal Routes
|--------------------------------------------------------------------------
|
| Routes registered in this file are automatically prefixed with "/c-portal",
| named with "customer.", and wrapped in the "customer.web" middleware group
| via bootstrap/app.php.
|
*/

Route::get('/', [LoginController::class, 'entry'])->name('index');

Route::middleware('customer.guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:customer-portal-login')
        ->name('login.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])
        ->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:customer-portal-forgot-password')
        ->name('password.otp.send');

    Route::get('/verify-otp', [OtpVerificationController::class, 'create'])
        ->name('password.otp');
    Route::post('/verify-otp', [OtpVerificationController::class, 'store'])
        ->middleware('throttle:customer-portal-otp-verify')
        ->name('password.otp.verify');
    Route::post('/resend-otp', [OtpVerificationController::class, 'resend'])
        ->middleware('throttle:customer-portal-forgot-password')
        ->name('password.otp.resend');

    Route::middleware('customer.password-reset-verified')->group(function () {
        Route::get('/reset-password', [ResetPasswordController::class, 'create'])
            ->name('password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'store'])
            ->middleware('throttle:customer-portal-password-reset')
            ->name('password.update');
    });
});

Route::middleware(['customer.auth', 'customer.access'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('customer.auth')
    ->name('logout');
