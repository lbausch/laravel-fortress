<?php

namespace Bausch\LaravelFortress\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyGlobalRole
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string                   $role_name
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $role_name)
    {
        if (!$request->user()->hasRole($role_name)) {
            throw new HttpException(403, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
