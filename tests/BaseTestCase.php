<?php

namespace Tests;

use Bausch\Fortress\FortressServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class BaseTestCase extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load factories
        $this->withFactories(__DIR__.'/factories');

        // Load migrations
        $this->loadLaravelMigrations(['--database' => 'testbench']);

        // Load test migrations
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Migrate database
        $this->artisan('migrate', ['--database' => 'testbench'])->run();
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Load Service Provider.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            FortressServiceProvider::class,
        ];
    }
}
