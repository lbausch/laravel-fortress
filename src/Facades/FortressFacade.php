<?php

namespace Bausch\LaravelFortress\Facades;

use Illuminate\Support\Facades\Facade;

class FortressFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Bausch\LaravelFortress\Contracts\Fortress::class;
    }
}
