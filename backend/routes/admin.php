<?php

use App\Admin\Controllers\AdminContactMessageController;
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

    Route::get('/contact-messages', [AdminContactMessageController::class, 'index']);
    Route::get('/contact-messages/{message}', [AdminContactMessageController::class, 'show']);

    $writeThrottle = 'throttle:'.AdminConstants::ADMIN_WRITE_THROTTLE_MAX_ATTEMPTS.','.AdminConstants::ADMIN_WRITE_THROTTLE_DECAY_MINUTES.',admin';

    Route::post('/contact-messages/{message}/read', [AdminContactMessageController::class, 'read'])
        ->middleware($writeThrottle);
    Route::post('/contact-messages/{message}/close', [AdminContactMessageController::class, 'close'])
        ->middleware($writeThrottle);
    Route::post('/contact-messages/{message}/reopen', [AdminContactMessageController::class, 'reopen'])
        ->middleware($writeThrottle);
    Route::post('/contact-messages/{message}/replies', [AdminContactMessageController::class, 'storeReply'])
        ->middleware($writeThrottle);
    Route::post('/contact-messages/{message}/replies/{reply}/retry', [AdminContactMessageController::class, 'retryReply'])
        ->middleware($writeThrottle);
});
