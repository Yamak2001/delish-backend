<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\OrderProcessingService::class);
        $this->app->singleton(\App\Services\JobTicketService::class);
        $this->app->singleton(\App\Services\PricingService::class);
        $this->app->singleton(\App\Services\WasteManagementService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::enablePasswordGrant();
    }
}
