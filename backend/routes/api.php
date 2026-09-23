<?php

use App\Auth\Controllers\EmailVerificationController;
use App\Auth\Controllers\LoginController;
use App\Auth\Controllers\PasswordResetController;
use App\Auth\Controllers\RegisterController;
use App\Contact\Controllers\ContactController;
use App\Farming\Controllers\FarmController;
use App\Farming\Controllers\PlotController;
use App\Farming\Controllers\RestrictedZoneController;
use App\Http\Controllers\Api\V1\CropRecommendationController;
use App\Http\Controllers\Api\V1\ForwardContractController;
use App\Http\Controllers\Api\V1\HarvestListingController;
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
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:6,1')->name('password.email');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');

    // Public Contact
    Route::post('/contact', ContactController::class);

    // Public Marketplace
    Route::get('/market/contracts', [MarketplaceController::class, 'index']); // Kept name for backwards compatibility
    Route::get('/market/items/{type}/{id}', [MarketplaceController::class, 'show']);

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
            Route::post('/farms', [FarmController::class, 'store'])->middleware('verified');
            Route::get('/farms/{farm}/plots', [PlotController::class, 'index']);
            Route::post('/farms/{farm}/plots', [PlotController::class, 'store'])->middleware('verified');
            Route::get('/plots', [PlotController::class, 'allUserPlots']);
            Route::get('/restricted-zones', [RestrictedZoneController::class, 'index']);

            // Crop Recommendations
            Route::post('/plots/{plot}/analyze', [CropRecommendationController::class, 'analyze'])->middleware(['throttle:30,1', 'verified']);
            Route::get('/plots/{plot}/recommendations', [CropRecommendationController::class, 'index']);
            Route::patch('/recommendations/{recommendation}/status', [CropRecommendationController::class, 'updateStatus'])->middleware('verified');

            // Forward Contracts
            Route::post('/recommendations/{recommendation}/publish', [ForwardContractController::class, 'store'])->middleware('verified');
            Route::get('/farmer/contracts/stats', [ForwardContractController::class, 'stats']);
            Route::get('/farmer/contracts', [ForwardContractController::class, 'index']);
            Route::get('/farmer/contracts/{contract}', [ForwardContractController::class, 'show']);
            Route::patch('/farmer/contracts/{contract}/cancel', [ForwardContractController::class, 'cancel'])->middleware('verified');

            // Manual Listings & Cash Approvals
            Route::post('/farmer/listings', [HarvestListingController::class, 'store'])->middleware('verified');
            Route::patch('/farmer/listings/{listing}/cancel', [HarvestListingController::class, 'cancel'])->middleware('verified');
            Route::get('/farmer/purchases', [PurchaseController::class, 'farmerPurchases']);
            Route::post('/farmer/purchases/{purchase}/approve', [PurchaseController::class, 'approveCashPayment'])->middleware('verified');
        });

        // Buyer Routes
        Route::middleware(EnsureUserHasRole::class.':buyer')->group(function () {
            Route::post('/market/{type}/{id}/checkout', [PurchaseController::class, 'checkout'])->middleware(['throttle:10,1', 'verified']);
            Route::post('/checkout/{session_id}/cancel', [PurchaseController::class, 'cancelCheckout']);
            Route::get('/checkout/{session_id}/verify', [PurchaseController::class, 'verifyCheckout']);
            Route::get('/buyer/purchases', [PurchaseController::class, 'index']);
            Route::get('/buyer/purchases/{purchase}', [PurchaseController::class, 'show']);
        });
    });
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->withoutMiddleware(['auth:sanctum'])
    ->name('verification.verify');
