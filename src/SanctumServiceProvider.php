<?php

namespace Laravel\Sanctum;

use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Console\Commands\PruneExpired;

class SanctumServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        config([
            'auth.guards.sanctum' => array_merge([
                'driver' => 'sanctum',
                'provider' => null,
            ], config('auth.guards.sanctum', [])),
        ]);

        $this->mergeConfigFrom(__DIR__ . '/../config/sanctum.php', 'sanctum');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (app()->runningInConsole()) {
            $this->registerMigrations();

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'sanctum-migrations');

            $this->publishes([
                __DIR__ . '/../config/sanctum.php' => base_path('config/sanctum.php'),
            ], 'sanctum-config');

            $this->commands([
                PruneExpired::class,
            ]);
        }

        $this->configureGuard();
    }

    /**
     * Register Sanctum's migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        if (Sanctum::shouldRunMigrations()) {
            return $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Configure the Sanctum authentication guard.
     *
     * @return void
     */
    protected function configureGuard()
    {
        Auth::resolved(function ($auth) {
            $auth->extend('sanctum', function ($app, $name, array $config) use ($auth) {
                return tap($this->createGuard($auth, $config), function ($guard) {
                    app()->refresh('request', $guard, 'setRequest');
                });
            });
        });
    }

    /**
     * Register the guard.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @param  array  $config
     * @return RequestGuard
     */
    protected function createGuard($auth, $config)
    {
        $sanctum = new Guard($auth, config('sanctum.expiration'), $config['provider']);

        return new SanctumGuard(
            $sanctum,
            $this->app['request'],
            $auth->createUserProvider($config['provider'])
        );
    }
}
