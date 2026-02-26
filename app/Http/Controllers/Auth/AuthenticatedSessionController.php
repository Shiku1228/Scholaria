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
        $role = (string) (data_get($user, 'role') ?? 'student');

        $roleDefaultRedirect = match ($role) {
            'admin' => route('admin.dashboard'),
            'teacher' => route('teacher.dashboard'),
            default => route('student.dashboard'),
        };

        $intended = $request->session()->pull('url.intended');
        if (is_string($intended) && $this->intendedMatchesRole($intended, $role)) {
            return redirect()->to($intended);
        }

        return redirect()->to($roleDefaultRedirect);
    }

    private function intendedMatchesRole(string $url, string $role): bool
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');

        return match ($role) {
            'admin' => str_starts_with($path, '/admin'),
            'teacher' => str_starts_with($path, '/teacher'),
            'student' => str_starts_with($path, '/student'),
            default => false,
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
