<?php

use App\Admin\Controllers\AdminContactMessageController;
use App\Admin\Controllers\AdminContentController;
use App\Admin\Controllers\AdminReportController;
use App\Admin\Controllers\AdminUserController;
use App\Constants\AdminConstants;
use App\Domain\Shared\Enums\ReportTargetType;
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

    Route::get('/reports', [AdminReportController::class, 'index']);
    Route::get('/reports/{report}', [AdminReportController::class, 'show'])->whereNumber('report');
    Route::post('/reports/{report}/decision', [AdminReportController::class, 'decision'])
        ->whereNumber('report')
        ->middleware($writeThrottle);

    $contentTypes = array_map(fn (ReportTargetType $type): string => $type->value, ReportTargetType::cases());

    Route::get('/content/{type}', [AdminContentController::class, 'index'])->whereIn('type', $contentTypes);
    Route::get('/content/{type}/{id}', [AdminContentController::class, 'show'])
        ->whereIn('type', $contentTypes)->whereNumber('id');
    Route::post('/content/{type}/{id}/hide', [AdminContentController::class, 'hide'])
        ->whereIn('type', $contentTypes)->whereNumber('id')->middleware($writeThrottle);
    Route::post('/content/{type}/{id}/restore', [AdminContentController::class, 'restore'])
        ->whereIn('type', $contentTypes)->whereNumber('id')->middleware($writeThrottle);
});
