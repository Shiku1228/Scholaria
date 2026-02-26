<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $role = (string) $request->query('role', 'all');
        $status = (string) $request->query('status', 'active');

        $query = User::query();

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status === 'all') {
            $query->withTrashed();
        }

        if (in_array($role, ['teacher', 'student'], true)) {
            $query->where('role', $role);
        }

        $users = $query
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => [
                'role' => $role,
                'status' => $status,
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
            'role' => ['required', Rule::in(['teacher', 'student'])],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

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
            'role' => ['required', Rule::in(['teacher', 'student'])],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index');
    }

    public function destroy($user): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($user);

        if ($user->role === 'admin') {
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

        if ($user->role === 'admin') {
            abort(403);
        }

        if ($user->trashed()) {
            $user->restore();
        }

        return redirect()->route('admin.users.index');
    }
}
