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

        $hasStudentNumberColumn = Schema::hasColumn('users', 'student_number');

        if ($search !== '') {
            $query->where(function ($inner) use ($search, $hasStudentNumberColumn) {
                $inner
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');

                if ($hasStudentNumberColumn) {
                    $inner->orWhere('student_number', 'like', '%' . $search . '%');
                }
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
        $hasStudentNumberColumn = Schema::hasColumn('users', 'student_number');
        $hasFirstNameColumn = Schema::hasColumn('users', 'first_name');
        $hasMiddleNameColumn = Schema::hasColumn('users', 'middle_name');
        $hasLastNameColumn = Schema::hasColumn('users', 'last_name');

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLE_OPTIONS)],
        ];

        if ($hasStudentNumberColumn) {
            $rules['student_number'] = ['nullable', 'string', 'max:255', 'unique:users,student_number'];
        }

        $validated = $request->validate($rules);

        if ($hasStudentNumberColumn && ($validated['role'] ?? '') === 'Student' && empty($validated['student_number'])) {
            return back()->withErrors([
                'student_number' => 'Student number is required for student accounts.',
            ])->withInput();
        }

        $name = trim(implode(' ', array_filter([
            $validated['first_name'] ?? '',
            $validated['middle_name'] ?? '',
            $validated['last_name'] ?? '',
        ])));

        $payload = [
            'name' => $name,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ];

        if ($hasFirstNameColumn) {
            $payload['first_name'] = $validated['first_name'];
        }
        if ($hasMiddleNameColumn) {
            $payload['middle_name'] = $validated['middle_name'] ?? null;
        }
        if ($hasLastNameColumn) {
            $payload['last_name'] = $validated['last_name'];
        }
        if ($hasStudentNumberColumn) {
            $payload['student_number'] = ($validated['role'] ?? '') === 'Student' ? ($validated['student_number'] ?? null) : null;
        }

        $user = User::query()->create($payload);

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

        $hasStudentNumberColumn = Schema::hasColumn('users', 'student_number');
        $hasFirstNameColumn = Schema::hasColumn('users', 'first_name');
        $hasMiddleNameColumn = Schema::hasColumn('users', 'middle_name');
        $hasLastNameColumn = Schema::hasColumn('users', 'last_name');

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLE_OPTIONS)],
        ];

        if ($hasStudentNumberColumn) {
            $rules['student_number'] = ['nullable', 'string', 'max:255', Rule::unique('users', 'student_number')->ignore($user->id)];
        }

        $validated = $request->validate($rules);

        if ($hasStudentNumberColumn && ($validated['role'] ?? '') === 'Student' && empty($validated['student_number'])) {
            return back()->withErrors([
                'student_number' => 'Student number is required for student accounts.',
            ])->withInput();
        }

        $name = trim(implode(' ', array_filter([
            $validated['first_name'] ?? '',
            $validated['middle_name'] ?? '',
            $validated['last_name'] ?? '',
        ])));

        $user->name = $name;
        if ($hasFirstNameColumn) {
            $user->first_name = $validated['first_name'];
        }
        if ($hasMiddleNameColumn) {
            $user->middle_name = $validated['middle_name'] ?? null;
        }
        if ($hasLastNameColumn) {
            $user->last_name = $validated['last_name'];
        }
        if ($hasStudentNumberColumn) {
            $user->student_number = ($validated['role'] ?? '') === 'Student' ? ($validated['student_number'] ?? null) : null;
        }
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
