<?php

use App\Auth\Controllers\EmailVerificationController;
use App\Auth\Controllers\LoginController;
use App\Auth\Controllers\RegisterController;
use App\Contact\Controllers\ContactController;
use App\Farming\Controllers\FarmController;
use App\Farming\Controllers\PlotController;
use App\Http\Controllers\Api\V1\CropRecommendationController;
use App\Shared\Middleware\EnsureUserHasRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Auth
    Route::post('/register', RegisterController::class);
    Route::post('/login', [LoginController::class, 'login']);

    // Public Contact
    Route::post('/contact', ContactController::class);

    // Protected Auth routes
    Route::middleware('auth:sanctum')->group(function () {
        // WebSocket auth - manual endpoint to avoid 'login' route redirect (API-only app)
        Route::post('/broadcasting/auth', function (Request $request) {
            return Broadcast::auth($request);
        });

        Route::post('/logout', [LoginController::class, 'logout']);
        Route::get('/user', [LoginController::class, 'user']);

        // Email Verification
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware(['throttle:6,1'])
            ->name('verification.send');

        // Farming (Farmers only)
        Route::middleware(EnsureUserHasRole::class.':farmer')->group(function () {
            Route::get('/farms', [FarmController::class, 'index']);
            Route::post('/farms', [FarmController::class, 'store']);
            Route::get('/farms/{farm}/plots', [PlotController::class, 'index']);
            Route::post('/farms/{farm}/plots', [PlotController::class, 'store']);

            // Crop Recommendations
            Route::post('/plots/{plot}/analyze', [CropRecommendationController::class, 'analyze'])->middleware('throttle:5,60');
            Route::get('/plots/{plot}/recommendations', [CropRecommendationController::class, 'index']);
            Route::patch('/recommendations/{recommendation}/status', [CropRecommendationController::class, 'updateStatus']);
        });
    });
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['auth:sanctum', 'signed'])
    ->name('verification.verify');
