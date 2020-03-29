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
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'marcot89');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'marcot89');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravelmagicroutes.php', 'laravelmagicroutes');

        // Register the service the package provides.
        $this->app->singleton('laravelmagicroutes', function ($app) {
            return new LaravelMagicRoutes;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laravelmagicroutes'];
    }
    
    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/laravelmagicroutes.php' => config_path('laravelmagicroutes.php'),
        ], 'laravelmagicroutes.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/marcot89'),
        ], 'laravelmagicroutes.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/marcot89'),
        ], 'laravelmagicroutes.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/marcot89'),
        ], 'laravelmagicroutes.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
