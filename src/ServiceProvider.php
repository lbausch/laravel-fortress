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
        $this->publishes([__DIR__.'/../config/fortress.php' => config_path('fortress.php')]);
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
