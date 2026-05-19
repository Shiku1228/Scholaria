<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            abort(401, 'Unauthorized');
        }

        $user = Auth::user();

        // Any Spatie role that is not Teacher and not Student is considered Admin
        if (method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames();
            if ($roles->isNotEmpty() && $roles->diff(['Teacher', 'Student'])->isNotEmpty()) {
                return $next($request);
            }
        }

        // Fallback for legacy role column or presence of admin profile
        if (data_get($user, 'role') === 'admin' || (method_exists($user, 'admin') && $user->admin()->exists())) {
            return $next($request);
        }

        abort(403, 'User does not have the right roles.');
    }
}
