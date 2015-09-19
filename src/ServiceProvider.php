<?php

namespace Bausch\LaravelFortress;

use Bausch\LaravelFortress\Contracts\Fortress as FortressContract;
use Bausch\LaravelFortress\Contracts\FortressGuard as GuardContract;
use Bausch\LaravelFortress\Facades\FortressFacade;
use Illuminate\Foundation\AliasLoader;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Publish migrations
        $this->publishes([
            __DIR__.'/database/migrations/' => database_path('/migrations'),
        ], 'migrations');

        // Publish config file
        $this->publishes([__DIR__.'/../config/laravel-fortress.php' => config_path('laravel-fortress.php')]);

        // Register global Roles and Permissions
        $global_roles = config('laravel-fortress', []);

        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);

        foreach ($global_roles as $role_name => $permissions) {
            foreach ($permissions as $permission_name) {
                $gate->define($permission_name, function ($model) use ($permission_name) {
                    return $model->hasPermission($permission_name);
                });
            }
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Bind Interfaces to Implementation
        $this->app->bind(FortressContract::class, \Bausch\LaravelFortress\Fortress::class);
        $this->app->bind(GuardContract::class, \Bausch\LaravelFortress\Guard::class);

        // Register Alias
        AliasLoader::getInstance()->alias('Fortress', FortressFacade::class);
    }
}
