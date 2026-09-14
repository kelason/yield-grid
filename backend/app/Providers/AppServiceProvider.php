<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Broadcast;
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

        // Load channel definitions without auto-registering the legacy web broadcasting/auth route
        require base_path('routes/channels.php');
    }
}
