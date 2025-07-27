<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PaymentGateways\PaymentGatewayManager;
use App\Services\PaymentGateways\PaymentService;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PaymentGatewayManager as singleton
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            return new PaymentGatewayManager();
        });

        // Register PaymentService as singleton
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService($app->make(PaymentGatewayManager::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}