<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware as SpatieRoleMiddleware;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $middleware = new SpatieRoleMiddleware();

        return $middleware->handle($request, $next, $role);
    }
}
