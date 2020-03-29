<?php

namespace MarcoT89\LaravelMagicRoutes;

use Illuminate\Support\ServiceProvider;

class LaravelMagicRoutesServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        LaravelMagicRoutes::boot();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // $this->mergeConfigFrom(__DIR__.'/../config/magicroutes.php', 'magicroutes');

        // Register the service the package provides.
        $this->app->singleton('laravelmagicroutes', function ($app) {
            return new LaravelMagicRoutes;
        });
    }
}
