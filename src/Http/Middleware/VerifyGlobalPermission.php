<?php

namespace Bausch\LaravelFortress\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyGlobalPermission
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @throws HttpException
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $permission_name)
    {
        if (!$request->user()->hasGlobalPermission($permission_name)) {
            throw new HttpException(403, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
