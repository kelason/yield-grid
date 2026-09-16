<?php

use App\Auth\Controllers\EmailVerificationController;
use App\Auth\Controllers\LoginController;
use App\Auth\Controllers\RegisterController;
use App\Contact\Controllers\ContactController;
use App\Farming\Controllers\FarmController;
use App\Farming\Controllers\PlotController;
use App\Farming\Controllers\RestrictedZoneController;
use App\Http\Controllers\Api\V1\CropRecommendationController;
use App\Http\Controllers\Api\V1\ForwardContractController;
use App\Http\Controllers\Api\V1\MarketplaceController;
use App\Http\Controllers\Api\V1\PayMongoWebhookController;
use App\Http\Controllers\Api\V1\PurchaseController;
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

    // Public Marketplace
    Route::get('/market/contracts', [MarketplaceController::class, 'index']);
    Route::get('/market/contracts/{contract}', [MarketplaceController::class, 'show']);

    // PayMongo Webhooks
    Route::post('/webhooks/paymongo', [PayMongoWebhookController::class, 'handle']);

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
            Route::get('/plots', [PlotController::class, 'allUserPlots']);
            Route::get('/restricted-zones', [RestrictedZoneController::class, 'index']);

            // Crop Recommendations
            Route::post('/plots/{plot}/analyze', [CropRecommendationController::class, 'analyze'])->middleware('throttle:5,60');
            Route::get('/plots/{plot}/recommendations', [CropRecommendationController::class, 'index']);
            Route::patch('/recommendations/{recommendation}/status', [CropRecommendationController::class, 'updateStatus']);

            // Forward Contracts
            Route::post('/recommendations/{recommendation}/publish', [ForwardContractController::class, 'store']);
            Route::get('/farmer/contracts', [ForwardContractController::class, 'index']);
            Route::get('/farmer/contracts/{contract}', [ForwardContractController::class, 'show']);
            Route::patch('/farmer/contracts/{contract}/cancel', [ForwardContractController::class, 'cancel']);
        });

        // Buyer Routes
        Route::middleware(EnsureUserHasRole::class.':buyer')->group(function () {
            Route::post('/market/contracts/{contract}/checkout', [PurchaseController::class, 'checkout'])->middleware('throttle:10,1');
            Route::get('/buyer/purchases', [PurchaseController::class, 'index']);
            Route::get('/buyer/purchases/{purchase}', [PurchaseController::class, 'show']);
        });
    });
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['auth:sanctum', 'signed'])
    ->name('verification.verify');
