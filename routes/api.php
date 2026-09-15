<?php

use App\Http\Controllers\Api\MeetingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.key', 'throttle:api-client'])->group(function () {
    Route::get('/meetings/active', [MeetingController::class, 'active']);
});
