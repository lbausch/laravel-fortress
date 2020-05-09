<?php

namespace Bausch\Fortress;

use Illuminate\Support\ServiceProvider;

class FortressServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerMigrations();
        }
    }

    /**
     * Register Fortress's migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        if (Fortress::shouldRunMigrations()) {
            return $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }
}
