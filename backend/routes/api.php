<?php

use Illuminate\Support\Facades\Route;
use App\Auth\Controllers\RegisterController;
use App\Auth\Controllers\LoginController;
use App\Auth\Controllers\EmailVerificationController;
use App\Contact\Controllers\ContactController;

Route::prefix('v1')->group(function () {
    // Public Auth
    Route::post('/register', RegisterController::class);
    Route::post('/login', [LoginController::class, 'login']);

    // Public Contact
    Route::post('/contact', ContactController::class);

    // Protected Auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::get('/user', [LoginController::class, 'user']);

        // Email Verification
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware(['throttle:6,1'])
            ->name('verification.send');
            
        // Farming (Farmers only)
        Route::middleware(\App\Shared\Middleware\EnsureUserHasRole::class . ':farmer')->group(function () {
            Route::get('/farms', [\App\Farming\Controllers\FarmController::class, 'index']);
            Route::post('/farms', [\App\Farming\Controllers\FarmController::class, 'store']);
            Route::get('/farms/{farm}/plots', [\App\Farming\Controllers\PlotController::class, 'index']);
            Route::post('/farms/{farm}/plots', [\App\Farming\Controllers\PlotController::class, 'store']);
        });
    });
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['auth:sanctum', 'signed'])
    ->name('verification.verify');
