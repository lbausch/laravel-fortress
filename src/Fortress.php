<?php

namespace Bausch\Fortress;

class Fortress
{
    /**
     * Indicates if Fortress's migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Determine if Fortress's migrations should be run.
     *
     * @return bool
     */
    public static function shouldRunMigrations()
    {
        return static::$runsMigrations;
    }

    /**
     * Configure Fortress to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static();
    }
}
