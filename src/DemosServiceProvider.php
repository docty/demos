<?php

namespace Docty\Demos;

use Illuminate\Support\ServiceProvider;

class DemosServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'docty');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'docty');
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
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/demos.php', 'demos');

        // Register the service the package provides.
        $this->app->singleton('demos', function ($app) {
            return new Demos;
        });

        $this->app->singleton('loancalculator', function ($app) {
            return new LoanCalculator;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['demos'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/demos.php' => config_path('demos.php'),
        ], 'demos.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/docty'),
        ], 'demos.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/docty'),
        ], 'demos.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/docty'),
        ], 'demos.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
