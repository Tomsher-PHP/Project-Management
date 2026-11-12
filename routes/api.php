<?php

use App\Http\Controllers\Api\DataSync\SettingsController;
use App\Http\Controllers\Api\MeetingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.key', 'throttle:api-client'])->group(function () {
    Route::get('/meetings/active', [MeetingController::class, 'active']);

    Route::prefix('data-sync/settings')
        ->as('api.data-sync.settings.')
        ->group(function () {
            Route::get('/{module}', [SettingsController::class, 'index'])->name('index');
            Route::get('/{module}/{code}', [SettingsController::class, 'show'])->name('show');
            Route::post('/{module}', [SettingsController::class, 'store'])->name('store');
            Route::put('/{module}/{code}', [SettingsController::class, 'update'])->name('update');
            Route::delete('/{module}/{code}', [SettingsController::class, 'destroy'])->name('destroy');
        });
});
