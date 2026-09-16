<?php

namespace App\Providers;

use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use App\Policies\CropRecommendationPolicy;
use App\Policies\PlotPolicy;
use Domain\Farming\Models\Plot;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent auth middleware from redirecting to 'login' route (API-only app)
        Authenticate::redirectUsing(fn () => null);

        // Register authorization policies
        Gate::policy(Plot::class, PlotPolicy::class);
        Gate::policy(CropRecommendation::class, CropRecommendationPolicy::class);
        Gate::policy(\App\Domain\Marketplace\Models\ForwardContract::class, \App\Policies\ForwardContractPolicy::class);

        // Load channel definitions without auto-registering the legacy web broadcasting/auth route
        require base_path('routes/channels.php');
    }
}
