<?php

namespace Bausch\LaravelFortress\Traits;

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait AuthorizesGlobalRequests
{
    /**
     * Authorize global.
     *
     * @param string $ability
     *
     * @throws HttpException
     */
    public function authorizeGlobal($ability)
    {
        Auth::user()->callFortressGuard()
            ->authorizeGlobal($ability);
    }

    /**
     * Authorize for user global.
     *
     * @param object $user
     * @param string $ability
     *
     * @throws HttpException
     */
    public function authorizeForUserGlobal($user, $ability)
    {
        // @TODO
    }
}
