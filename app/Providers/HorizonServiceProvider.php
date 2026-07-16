<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * Access is granted via a secret token passed as a query parameter.
     * Usage: https://api.everydaypak.com/horizon?token=YOUR_HORIZON_SECRET
     *
     * In local environment, access is always granted.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            // Always allow access in local development
            if (app()->environment('local')) {
                return true;
            }

            // In production: validate secret token from query string
            $secret = config('app.horizon_secret');

            return $secret && request()->query('token') === $secret;
        });
    }
}
