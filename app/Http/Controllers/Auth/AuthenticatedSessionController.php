<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(): \Illuminate\View\View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Rate limiting to prevent brute force attacks
        $this->ensureIsNotRateLimited($request);

        $remember = (bool) $request->boolean('remember');

        $login = $request->string('login')->toString();

        $attempted = Auth::attempt(['email' => $login, 'password' => $request->input('password')], $remember)
            || Auth::attempt(['name' => $login, 'password' => $request->input('password')], $remember);

        if (!$attempted) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        $user = $request->user();

        // Check if user has 2FA enabled
        if ($user->google2fa_enabled) {
            return redirect()->route('2fa.verify.show');
        }

        // Check for ALL admin roles first - ABSOLUTE PRIORITY
        $adminRoles = ['Admin', 'Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'];
        
        // Debug: Log role detection attempt
        Log::info('Login redirect check for user ' . $user->id . ' with email: ' . $user->email);
        
        if (method_exists($user, 'hasRole')) {
            Log::info('User has hasRole method, checking admin roles...');
            
            // Check if user has ANY admin role - IMMEDIATE RETURN
            foreach ($adminRoles as $adminRole) {
                if ($user->hasRole($adminRole)) {
                    Log::info("✅ Admin role '$adminRole' detected, redirecting to admin dashboard");
                    return redirect()->route('admin.dashboard');
                }
            }
            
            Log::info('No admin roles found, checking other roles...');
        } else {
            Log::error('❌ User does not have hasRole method!');
        }
        
        // Check for Teacher role if no admin role found
        if (method_exists($user, 'hasRole') && $user->hasRole('Teacher')) {
            Log::info('Teacher role detected, redirecting to teacher dashboard');
            return redirect()->route('teacher.dashboard');
        }
        
        // Check for Student role if no other role found
        if (method_exists($user, 'hasRole') && $user->hasRole('Student')) {
            Log::info('Student role detected, redirecting to student dashboard');
            return redirect()->route('student.dashboard');
        }
        
        // Fallback to legacy role check
        Log::info('No modern roles found, checking legacy role field...');
        $legacyRole = (string) data_get($user, 'role', '');
        Log::info("Legacy role field: '$legacyRole'");
        
        if ($legacyRole === 'admin') {
            Log::info('Legacy admin role found, redirecting to admin dashboard');
            return redirect()->route('admin.dashboard');
        } elseif ($legacyRole === 'teacher') {
            Log::info('Legacy teacher role found, redirecting to teacher dashboard');
            return redirect()->route('teacher.dashboard');
        } elseif ($legacyRole === 'student') {
            Log::info('Legacy student role found, redirecting to student dashboard');
            return redirect()->route('student.dashboard');
        }
        
        // Final fallback to student dashboard
        Log::warning('No role detected, defaulting to student dashboard for user ' . $user->id);
        return redirect()->route('student.dashboard');
    }

    private function intendedMatchesRole(string $url, $user): bool
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');

        // Check for ALL admin roles
        $adminRoles = ['Admin', 'Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'];
        
        if (method_exists($user, 'hasRole')) {
            foreach ($adminRoles as $adminRole) {
                if ($user->hasRole($adminRole)) {
                    return str_starts_with($path, '/admin');
                }
            }
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('Teacher')) {
            return str_starts_with($path, '/teacher');
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('Student')) {
            return str_starts_with($path, '/student');
        }

        if (method_exists($user, 'getRoleNames') && $user->getRoleNames()->isEmpty()) {
            $legacyRole = (string) data_get($user, 'role', '');
            if ($legacyRole === 'admin') {
                return str_starts_with($path, '/admin');
            }
            if ($legacyRole === 'teacher') {
                return str_starts_with($path, '/teacher');
            }
            if ($legacyRole === 'student') {
                return str_starts_with($path, '/student');
            }
        }

        return false;
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return strtolower($request->input('login')).'|'.$request->ip();
    }
}
