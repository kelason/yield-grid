<?php

use App\Auth\Controllers\EmailVerificationController;
use App\Auth\Controllers\LoginController;
use App\Auth\Controllers\PasswordResetController;
use App\Auth\Controllers\RegisterController;
use App\Chat\Controllers\ChatConversationController;
use App\Chat\Controllers\ChatMessageController;
use App\Community\Controllers\ForumAttachmentController;
use App\Community\Controllers\ForumCategoryController;
use App\Community\Controllers\ForumReplyController;
use App\Community\Controllers\ForumReportController;
use App\Community\Controllers\ForumTagController;
use App\Community\Controllers\ForumThreadController;
use App\Community\Controllers\ForumVoteController;
use App\Constants\CreditScoringConstants;
use App\Constants\InsuranceConstants;
use App\Constants\ReportingConstants;
use App\Contact\Controllers\ContactController;
use App\CreditScoring\Controllers\CreditScoreController;
use App\CropRecommendation\Controllers\CropCompatibilityController;
use App\CropRecommendation\Controllers\CropRecommendationController;
use App\CropRecommendation\Controllers\CropTaxonomyController;
use App\Farming\Controllers\FarmController;
use App\Farming\Controllers\PlotController;
use App\Farming\Controllers\RestrictedZoneController;
use App\Insurance\Controllers\InsuranceController;
use App\Marketplace\Controllers\CropDemandController;
use App\Marketplace\Controllers\DemandOfferController;
use App\Marketplace\Controllers\ForwardContractController;
use App\Marketplace\Controllers\HarvestListingController;
use App\Marketplace\Controllers\MarketplaceController;
use App\Marketplace\Controllers\PayMongoWebhookController;
use App\Marketplace\Controllers\PriceComparisonController;
use App\Marketplace\Controllers\PriceGuideController;
use App\Marketplace\Controllers\PurchaseController;
use App\Shared\Controllers\ContentReportController;
use App\Shared\Middleware\AuthenticateIfTokenPresent;
use App\Shared\Middleware\EnsureUserHasMarketplaceAddress;
use App\Shared\Middleware\EnsureUserHasRole;
use App\Shared\Middleware\EnsureUserNotSuspended;
use App\Users\Controllers\GeoController;
use App\Users\Controllers\UserAddressController;
use App\Users\Controllers\UserProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Auth
    Route::post('/register', RegisterController::class)->middleware('throttle:10,1,register');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1,login');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:6,1')->name('password.email');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');

    // Public Contact
    Route::post('/contact', ContactController::class)->middleware('throttle:10,1,contact');

    // Public Marketplace
    Route::get('/market/contracts', [MarketplaceController::class, 'index'])->middleware(AuthenticateIfTokenPresent::class); // Kept name for backwards compatibility
    Route::get('/market/items/{type}/{id}', [MarketplaceController::class, 'show']);

    // Public reverse marketplace (buyer demands)
    Route::get('/market/demands', [CropDemandController::class, 'index'])->middleware(AuthenticateIfTokenPresent::class);
    Route::get('/market/demands/{demand}', [CropDemandController::class, 'show'])->middleware(AuthenticateIfTokenPresent::class);

    // Public PSGC geo cascade (registration needs it before login)
    Route::get('/market/prices/guide/batch', [PriceGuideController::class, 'batch'])->middleware('throttle:60,1');
    Route::get('/market/prices/guide/compare', [PriceComparisonController::class, 'compare'])->middleware('throttle:60,1');
    Route::get('/market/prices/guide', [PriceGuideController::class, 'guide'])->middleware('throttle:60,1');

    Route::get('/geo/regions', [GeoController::class, 'regions']);
    Route::get('/geo/provinces', [GeoController::class, 'provinces']);
    Route::get('/geo/cities-municipalities', [GeoController::class, 'citiesMunicipalities']);
    Route::get('/geo/barangays', [GeoController::class, 'barangays']);
    Route::get('/geo/center', [GeoController::class, 'center']);

    // PayMongo Webhooks
    Route::post('/webhooks/paymongo', [PayMongoWebhookController::class, 'handle']);

    // Public credit report verification (QR code in PDF reports)
    Route::get('/verify-report/{token}', [CreditScoreController::class, 'verifyReport'])->middleware('throttle:60,1');

    // Protected Auth routes
    Route::middleware(['auth:sanctum', EnsureUserNotSuspended::class])->group(function () {
        require __DIR__.'/admin.php';

        // WebSocket auth - manual endpoint to avoid 'login' route redirect (API-only app)
        Route::post('/broadcasting/auth', function (Request $request) {
            return Broadcast::auth($request);
        });

        Route::post('/logout', [LoginController::class, 'logout']);
        Route::get('/user', [LoginController::class, 'user']);

        // Public user profiles (all authenticated users)
        Route::get('/users/{user}', [UserProfileController::class, 'show']);

        // Own addresses (all authenticated users)
        Route::get('/user/addresses', [UserAddressController::class, 'index']);
        Route::post('/user/addresses', [UserAddressController::class, 'store'])->middleware('verified');
        Route::put('/user/addresses/{address}', [UserAddressController::class, 'update'])->middleware('verified');
        Route::delete('/user/addresses/{address}', [UserAddressController::class, 'destroy'])->middleware('verified');

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
            Route::get('/crop-taxonomy', [CropTaxonomyController::class, 'index']);
            Route::get('/crop-compatibility', [CropCompatibilityController::class, 'check']);

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

            // Reverse marketplace: competing offers on buyer demands
            Route::post('/demands/{demand}/offers', [DemandOfferController::class, 'store'])
                ->middleware(['verified', EnsureUserHasMarketplaceAddress::class]);
            Route::get('/farmer/offers', [DemandOfferController::class, 'myOffers']);
            Route::post('/farmer/offers/{offer}/withdraw', [DemandOfferController::class, 'withdraw'])->middleware('verified');
            Route::post('/farmer/offers/{offer}/cancel', [DemandOfferController::class, 'cancel'])->middleware('verified');
            Route::post('/farmer/offers/{offer}/mark-delivered', [DemandOfferController::class, 'markDelivered'])->middleware('verified');
            Route::post('/farmer/offers/{offer}/settle-balance', [DemandOfferController::class, 'settleBalance'])->middleware('verified');

            // Farmer Trust Score (CreditScoring)
            Route::get('/farmer/credit-score', [CreditScoreController::class, 'show'])
                ->middleware('throttle:'.CreditScoringConstants::SCORE_CALCULATE_THROTTLE_PER_HOUR.',1');
            Route::get('/farmer/credit-score/history', [CreditScoreController::class, 'history']);
            Route::post('/farmer/credit-score/report', [CreditScoreController::class, 'generateReport'])
                ->middleware(['verified', 'throttle:'.CreditScoringConstants::REPORT_GENERATE_THROTTLE_PER_DAY.',1440']);
            Route::get('/farmer/credit-score/report/{token}/download', [CreditScoreController::class, 'downloadReport']);

            // PCIC Crop Insurance
            Route::get('/farmer/insurance/profile', [InsuranceController::class, 'showProfile']);
            Route::put('/farmer/insurance/profile', [InsuranceController::class, 'updateProfile'])->middleware('verified');
            Route::get('/farmer/insurance/enrollments', [InsuranceController::class, 'indexEnrollments']);
            Route::post('/farmer/insurance/enrollments', [InsuranceController::class, 'storeEnrollment'])->middleware('verified');
            Route::get('/farmer/insurance/enrollments/{enrollment}', [InsuranceController::class, 'showEnrollment']);
            Route::patch('/farmer/insurance/enrollments/{enrollment}/status', [InsuranceController::class, 'advanceEnrollmentStatus'])->middleware('verified');
            Route::patch('/farmer/insurance/enrollments/{enrollment}/policy-details', [InsuranceController::class, 'recordPolicyDetails'])->middleware('verified');
            Route::post('/farmer/insurance/enrollments/{enrollment}/pack', [InsuranceController::class, 'requestPack'])
                ->middleware(['verified', 'throttle:'.InsuranceConstants::PACK_GENERATE_THROTTLE_PER_DAY.',1440']);
            Route::get('/farmer/insurance/enrollments/{enrollment}/pack/download', [InsuranceController::class, 'downloadPack'])
                ->middleware('throttle:'.InsuranceConstants::PACK_DOWNLOAD_THROTTLE_PER_DAY.',1440,pack-download');
            Route::get('/farmer/insurance/enrollments/{enrollment}/claims', [InsuranceController::class, 'indexClaims']);
            Route::post('/farmer/insurance/enrollments/{enrollment}/claims', [InsuranceController::class, 'storeClaim'])->middleware('verified');
            Route::patch('/farmer/insurance/claims/{claim}/advance', [InsuranceController::class, 'advanceClaimStatus'])->middleware('verified');
            Route::get('/farmer/insurance/reminders', [InsuranceController::class, 'indexReminders']);
            Route::get('/farmer/insurance/offices', [InsuranceController::class, 'indexOffices']);
        });

        // Buyer Routes
        Route::middleware(EnsureUserHasRole::class.':buyer')->group(function () {
            Route::post('/market/{type}/{id}/checkout', [PurchaseController::class, 'checkout'])->middleware(['throttle:10,1', 'verified']);
            Route::post('/checkout/{session_id}/cancel', [PurchaseController::class, 'cancelCheckout']);
            Route::get('/checkout/{session_id}/verify', [PurchaseController::class, 'verifyCheckout'])->middleware('throttle:10,1,verify-checkout');
            Route::get('/buyer/purchases', [PurchaseController::class, 'index']);
            Route::get('/buyer/purchases/{purchase}', [PurchaseController::class, 'show']);

            // Reverse marketplace: buyer demands + offer decisions
            Route::post('/buyer/demands', [CropDemandController::class, 'store'])
                ->middleware(['throttle:10,1', 'verified', EnsureUserHasMarketplaceAddress::class]);
            Route::get('/buyer/demands', [CropDemandController::class, 'myDemands']);
            Route::patch('/buyer/demands/{demand}/cancel', [CropDemandController::class, 'cancel'])->middleware('verified');
            Route::get('/buyer/demands/{demand}/offers', [DemandOfferController::class, 'indexForDemand']);
            Route::post('/buyer/offers/{offer}/accept', [DemandOfferController::class, 'accept'])->middleware('verified');
            Route::post('/buyer/offers/{offer}/checkout', [PurchaseController::class, 'checkoutOffer'])
                ->middleware(['throttle:10,1', 'verified', EnsureUserHasMarketplaceAddress::class]);
            Route::post('/buyer/offers/{offer}/reject', [DemandOfferController::class, 'reject'])->middleware('verified');
            Route::post('/buyer/offers/{offer}/cancel', [DemandOfferController::class, 'cancel'])->middleware('verified');
            Route::post('/buyer/offers/{offer}/confirm-completed', [DemandOfferController::class, 'confirmCompleted'])->middleware('verified');
        });

        // Offer detail (buyer of the demand or offering farmer, enforced by policy)
        Route::get('/offers/{offer}', [DemandOfferController::class, 'show']);

        // Community Forum (all authenticated users)
        Route::prefix('forum')->group(function () {
            Route::get('/categories', [ForumCategoryController::class, 'index']);
            Route::get('/tags', [ForumTagController::class, 'index']);
            Route::get('/threads', [ForumThreadController::class, 'index']);
            Route::get('/threads/{thread}', [ForumThreadController::class, 'show']);

            Route::middleware(['verified', EnsureUserHasRole::class.':farmer,buyer'])->group(function () {
                Route::post('/threads', [ForumThreadController::class, 'store']);
                Route::put('/threads/{thread}', [ForumThreadController::class, 'update']);
                Route::delete('/threads/{thread}', [ForumThreadController::class, 'destroy']);

                Route::post('/threads/{thread}/replies', [ForumReplyController::class, 'store']);
                Route::put('/replies/{reply}', [ForumReplyController::class, 'update']);
                Route::delete('/replies/{reply}', [ForumReplyController::class, 'destroy']);
                Route::post('/replies/{reply}/accept', [ForumReplyController::class, 'accept']);

                Route::post('/threads/{thread}/vote', [ForumVoteController::class, 'storeThreadVote']);
                Route::post('/replies/{reply}/vote', [ForumVoteController::class, 'storeReplyVote']);

                Route::post('/reports', [ForumReportController::class, 'store'])
                    ->middleware([
                        'throttle:'.ReportingConstants::REPORT_MINUTE_LIMITER,
                        'throttle:'.ReportingConstants::REPORT_DAILY_LIMITER,
                    ]);
                Route::post('/attachments', [ForumAttachmentController::class, 'store']);
            });
        });

        // Unified content reports (verified members only; shares limits with the legacy forum adapter)
        Route::post('/reports', [ContentReportController::class, 'store'])
            ->middleware([
                'verified',
                EnsureUserHasRole::class.':farmer,buyer',
                'throttle:'.ReportingConstants::REPORT_MINUTE_LIMITER,
                'throttle:'.ReportingConstants::REPORT_DAILY_LIMITER,
            ]);

        // Chat (verified members only)
        Route::prefix('chat')->middleware(['verified', EnsureUserHasRole::class.':farmer,buyer'])->group(function () {
            Route::get('/conversations', [ChatConversationController::class, 'index']);
            Route::post('/conversations', [ChatConversationController::class, 'store']);
            Route::get('/conversations/{conversation}', [ChatConversationController::class, 'show']);
            Route::post('/conversations/{conversation}/messages', [ChatMessageController::class, 'store']);
        });
    });
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->withoutMiddleware(['auth:sanctum'])
    ->name('verification.verify');
