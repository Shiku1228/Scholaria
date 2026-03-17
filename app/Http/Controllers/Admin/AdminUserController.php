<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    private const ROLE_OPTIONS = ['Admin', 'Teacher', 'Student'];

    public function index(Request $request): View
    {
        $role = (string) $request->query('role', 'all');
        $status = (string) $request->query('status', 'active');
        $search = trim((string) $request->query('q', ''));

        $query = User::query();

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status === 'all') {
            $query->withTrashed();
        }

        if (in_array($role, self::ROLE_OPTIONS, true)) {
            $query->role($role);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $users = $query
            ->orderBy('id', 'desc')
            ->paginate(7)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => [
                'role' => $role,
                'status' => $status,
                'q' => $search,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLE_OPTIONS)],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles([$validated['role']]);
        }

        $this->syncLegacyRoleColumn($user, $validated['role']);

        return redirect()->route('admin.users.index');
    }

    public function edit($user): View
    {
        $user = User::withTrashed()->findOrFail($user);

        return view('admin.users.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, $user): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLE_OPTIONS)],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles([$validated['role']]);
        }

        $this->syncLegacyRoleColumn($user, $validated['role']);

        return redirect()->route('admin.users.index');
    }

    private function syncLegacyRoleColumn(User $user, string $spatieRoleName): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            return;
        }

        $legacy = match ($spatieRoleName) {
            'Admin' => 'admin',
            'Teacher' => 'teacher',
            default => 'student',
        };

        $user->setAttribute('role', $legacy);
        $user->save();
    }

    public function destroy($user): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($user);

        if (method_exists($user, 'hasRole') && $user->hasRole('Admin')) {
            abort(403);
        }

        if (!$user->trashed()) {
            $user->delete();
        }

        return redirect()->back();
    }

    public function restore($user): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($user);

        if (method_exists($user, 'hasRole') && $user->hasRole('Admin')) {
            abort(403);
        }

        if ($user->trashed()) {
            $user->restore();
        }

        return redirect()->route('admin.users.index');
    }
}
