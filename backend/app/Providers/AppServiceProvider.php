<?php

namespace App\Providers;

use App\Domain\CropRecommendation\Repositories\CropRecommendationRepositoryInterface;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use App\Domain\Marketplace\Repositories\PurchaseRepositoryInterface;
use App\Domain\Marketplace\Services\PaymentGatewayInterface;
use App\Domain\Shared\Database\TransactionManagerInterface;
use App\Domain\Shared\Events\EventDispatcherInterface;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use App\Infrastructure\CropRecommendation\Repositories\EloquentCropRecommendationRepository;
use App\Infrastructure\Marketplace\Repositories\EloquentForwardContractRepository;
use App\Infrastructure\Marketplace\Repositories\EloquentPurchaseRepository;
use App\Infrastructure\Marketplace\Services\PayMongoService;
use App\Infrastructure\Shared\Database\LaravelTransactionManager;
use App\Infrastructure\Shared\Events\LaravelEventDispatcher;
use App\Policies\ConversationPolicy;
use App\Policies\CropRecommendationPolicy;
use App\Policies\ForwardContractPolicy;
use App\Policies\PlotPolicy;
use Domain\Farming\Models\Plot;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CropRecommendationRepositoryInterface::class, EloquentCropRecommendationRepository::class);
        $this->app->bind(ForwardContractRepositoryInterface::class, EloquentForwardContractRepository::class);
        $this->app->bind(PurchaseRepositoryInterface::class, EloquentPurchaseRepository::class);
        $this->app->bind(TransactionManagerInterface::class, LaravelTransactionManager::class);

        $this->app->bind(PaymentGatewayInterface::class, PayMongoService::class);
        $this->app->bind(EventDispatcherInterface::class, LaravelEventDispatcher::class);
    }

    public function boot(): void
    {
        // Prevent auth middleware from redirecting to 'login' route (API-only app)
        Authenticate::redirectUsing(fn () => null);

        // Customize the Email Verification URL to point to the frontend SPA
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $url = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            return config('app.frontend_url', 'http://localhost:5173').'/auth/verify-email?verify_url='.urlencode($url);
        });

        // Customize the Password Reset URL to point to the frontend SPA
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return config('app.frontend_url', 'http://localhost:5173').'/auth/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });

        // Register authorization policies
        Gate::policy(Plot::class, PlotPolicy::class);
        Gate::policy(CropRecommendation::class, CropRecommendationPolicy::class);
        Gate::policy(ForwardContract::class, ForwardContractPolicy::class);
        Gate::define(ConversationPolicy::CREATE_ABILITY, [ConversationPolicy::class, 'create']);

        // Load channel definitions without auto-registering the legacy web broadcasting/auth route
        require base_path('routes/channels.php');
    }
}
