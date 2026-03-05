<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $remember = (bool) $request->boolean('remember');

        $login = $request->string('login')->toString();

        $attempted = Auth::attempt(['email' => $login, 'password' => $request->input('password')], $remember)
            || Auth::attempt(['name' => $login, 'password' => $request->input('password')], $remember);

        if (!$attempted) {
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        $roleDefaultRedirect = route('student.dashboard');

        if (method_exists($user, 'hasRole') && $user->hasRole('Admin')) {
            $roleDefaultRedirect = route('admin.dashboard');
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('Teacher')) {
            $roleDefaultRedirect = route('teacher.dashboard');
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('Student')) {
            $roleDefaultRedirect = route('student.dashboard');
        } elseif (method_exists($user, 'getRoleNames') && $user->getRoleNames()->isEmpty()) {
            $legacyRole = (string) data_get($user, 'role', '');
            if ($legacyRole === 'admin') {
                $roleDefaultRedirect = route('admin.dashboard');
            } elseif ($legacyRole === 'teacher') {
                $roleDefaultRedirect = route('teacher.dashboard');
            } elseif ($legacyRole === 'student') {
                $roleDefaultRedirect = route('student.dashboard');
            }
        }

        $intended = $request->session()->pull('url.intended');
        if (is_string($intended) && $this->intendedMatchesRole($intended, $user)) {
            return redirect()->to($intended);
        }

        return redirect()->to($roleDefaultRedirect);
    }

    private function intendedMatchesRole(string $url, $user): bool
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');

        if (method_exists($user, 'hasRole') && $user->hasRole('Admin')) {
            return str_starts_with($path, '/admin');
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
}
