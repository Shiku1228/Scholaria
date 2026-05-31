<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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
        $roleDefaultRedirect = $this->roleRedirectFor($user);

        Log::info('Successful login redirect prepared', [
            'user_id' => $user?->id,
            'session_id' => $request->session()->getId(),
            'redirect_to' => $roleDefaultRedirect,
            'ip_address' => $request->ip(),
        ]);

        // Check if user has 2FA enabled
        if ($user->google2fa_enabled) {
            return redirect()->route('2fa.verify.show');
        }

        $intended = $request->session()->pull('url.intended');
        if (is_string($intended) && $this->intendedMatchesRole($intended, $user)) {
            return redirect()->to($intended);
        }

        return redirect()->to($roleDefaultRedirect);
    }

    private function roleRedirectFor($user): string
    {
        if (method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames();
            if ($roles->isNotEmpty()) {
                if ($roles->diff(['Teacher', 'Student'])->isNotEmpty()) {
                    return route('admin.dashboard');
                }

                if ($roles->contains('Teacher')) {
                    return route('teacher.dashboard');
                }

                if ($roles->contains('Student')) {
                    return route('student.dashboard');
                }
            }
        }

        $legacyRole = strtolower((string) data_get($user, 'role', ''));

        return match ($legacyRole) {
            'admin' => route('admin.dashboard'),
            'teacher' => route('teacher.dashboard'),
            default => route('student.dashboard'),
        };
    }

    private function intendedMatchesRole(string $url, $user): bool
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');

        if (method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames();
            if ($roles->isNotEmpty()) {
                if ($roles->diff(['Teacher', 'Student'])->isNotEmpty()) {
                    return str_starts_with($path, '/admin');
                }
                if ($roles->contains('Teacher')) {
                    return str_starts_with($path, '/teacher');
                }
                if ($roles->contains('Student')) {
                    return str_starts_with($path, '/student');
                }
            }
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
