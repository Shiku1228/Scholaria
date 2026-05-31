<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Check if user has 2FA enabled
        if ($user && $user->google2fa_enabled) {
            // Check if 2FA is verified in session
            if (!session('2fa_verified')) {
                return redirect()->route('2fa.verify.show');
            }
        }

        return $next($request);
    }
}
