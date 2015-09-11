<?php

namespace Bausch\LaravelFortress\Http\Middleware;

use Closure;

class VerifyGlobalRole
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // @TODO

        return $next($request);
    }
}
