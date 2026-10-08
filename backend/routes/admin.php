<?php

use App\Admin\Controllers\AdminUserController;
use App\Constants\AdminConstants;
use App\Shared\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['verified', EnsureUserHasRole::class.':admin'])->group(function () {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])
        ->middleware('throttle:'.AdminConstants::ADMIN_WRITE_THROTTLE_MAX_ATTEMPTS.','.AdminConstants::ADMIN_WRITE_THROTTLE_DECAY_MINUTES.',admin');
    Route::post('/users/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])
        ->middleware('throttle:'.AdminConstants::ADMIN_WRITE_THROTTLE_MAX_ATTEMPTS.','.AdminConstants::ADMIN_WRITE_THROTTLE_DECAY_MINUTES.',admin');
});
