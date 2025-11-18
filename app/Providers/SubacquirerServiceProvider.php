<?php

namespace App\Providers;

use App\Services\SubacquirerService;
use Illuminate\Support\ServiceProvider;

class SubacquirerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SubacquirerService::class, function ($app) {
            return new SubacquirerService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
