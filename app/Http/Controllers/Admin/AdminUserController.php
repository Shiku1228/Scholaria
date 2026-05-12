<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Load profile relationships for search
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('email', 'like', '%' . $search . '%');

                // Search in profile tables if possible
                $inner->orWhereHas('student', function ($q) use ($search) {
                    $q->where('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhere('student_number', 'like', '%' . $search . '%');
                });

                $inner->orWhereHas('teacher', function ($q) use ($search) {
                    $q->where('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhere('employee_id', 'like', '%' . $search . '%');
                });

                $inner->orWhereHas('admin', function ($q) use ($search) {
                    $q->where('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%');
                });
            });
        }

        $users = $query
            ->with(['student', 'teacher', 'admin'])
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
        $adminRoles = \Spatie\Permission\Models\Role::whereNotIn('name', ['Student', 'Teacher', 'Admin'])->get();
        return view('admin.users.create', compact('adminRoles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $role = $request->input('role');

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLE_OPTIONS)],
        ];

        // Add role-specific validation rules
        if ($role === 'Student') {
            $rules['student_number'] = ['required', 'string', 'max:255', 'unique:students,student_number'];
            $rules['year_level'] = ['nullable', 'string', 'max:255'];
            $rules['program'] = ['nullable', 'string', 'max:255'];
            $rules['college'] = ['nullable', 'string', 'max:255'];
        } elseif ($role === 'Teacher') {
            $rules['employee_id'] = ['required', 'string', 'max:255', 'unique:teachers,employee_id'];
            $rules['college'] = ['nullable', 'string', 'max:255'];
            $rules['program'] = ['nullable', 'string', 'max:255'];
            $rules['specialization'] = ['nullable', 'string', 'max:255'];
        } elseif ($role === 'Admin') {
            $rules['admin_level'] = ['nullable', 'string', 'max:255'];
            $rules['access_scope'] = ['nullable', 'string', 'max:255'];
            $rules['admin_role'] = ['required', 'string', 'exists:roles,name'];
        }

        // Temporary debug - dump all request data
        // dd($request->all());

        $validated = $request->validate($rules);

        // Debug: Log what data is being received
        \Log::info('Creating user with data:', [
            'role' => $role,
            'program' => $validated['program'] ?? 'NOT SET',
            'college' => $validated['college'] ?? 'NOT SET',
            'all_data' => $validated,
        ]);

        DB::transaction(function () use ($validated, $role) {
            // Create user (auth only)
            $user = User::create([
                'name' => trim("{$validated['first_name']} {$validated['middle_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Assign role
            if (method_exists($user, 'syncRoles')) {
                if ($role === 'Admin' && !empty($validated['admin_role'])) {
                    $user->syncRoles([$role, $validated['admin_role']]);
                } else {
                    $user->syncRoles([$role]);
                }
            }

            // Create profile based on role
            $profileData = [
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
            ];

            switch ($role) {
                case 'Student':
                    $profile = Student::create(array_merge($profileData, [
                        'student_number' => $validated['student_number'],
                        'year_level' => $validated['year_level'] ?? null,
                        'program' => $validated['program'] ?? null,
                        'college' => $validated['college'] ?? null,
                        'enrollment_date' => now(),
                    ]));
                    break;

                case 'Teacher':
                    $profile = Teacher::create(array_merge($profileData, [
                        'employee_id' => $validated['employee_id'],
                        'college' => $validated['college'] ?? null,
                        'program' => $validated['program'] ?? null,
                        'specialization' => $validated['specialization'] ?? null,
                        'hire_date' => now(),
                    ]));
                    break;

                case 'Admin':
                    $profile = Admin::create($profileData);
                    break;
            }

            // Update user with profile reference
            $user->update([
                'profile_type' => get_class($profile),
                'profile_id' => $profile->id,
            ]);
        });

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function show($user): View
    {
        $user = User::withTrashed()
            ->with(['student', 'teacher', 'admin'])
            ->findOrFail($user);

        $adminRoles = \Spatie\Permission\Models\Role::whereNotIn('name', ['Student', 'Teacher', 'Admin'])->get();

        return view('admin.users.show', [
            'user' => $user,
            'adminRoles' => $adminRoles,
        ]);
    }

    public function edit($user): View
    {
        $user = User::withTrashed()
            ->with(['student', 'teacher', 'admin'])
            ->findOrFail($user);

        $adminRoles = \Spatie\Permission\Models\Role::whereNotIn('name', ['Student', 'Teacher', 'Admin'])->get();

        return view('admin.users.edit', [
            'user' => $user,
            'adminRoles' => $adminRoles,
        ]);
    }

    public function update(Request $request, $user): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($user);
        $role = $request->input('role');

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLE_OPTIONS)],
        ];

        // Add role-specific validation rules
        if ($role === 'Student') {
            $rules['student_number'] = ['required', 'string', 'max:255', Rule::unique('students', 'student_number')->ignore($user->profile_id ?? null)];
            $rules['year_level'] = ['nullable', 'string', 'max:255'];
            $rules['program'] = ['nullable', 'string', 'max:255'];
            $rules['college'] = ['nullable', 'string', 'max:255'];
        } elseif ($role === 'Teacher') {
            $rules['employee_id'] = ['required', 'string', 'max:255', Rule::unique('teachers', 'employee_id')->ignore($user->profile_id ?? null)];
            $rules['college'] = ['nullable', 'string', 'max:255'];
            $rules['program'] = ['nullable', 'string', 'max:255'];
            $rules['specialization'] = ['nullable', 'string', 'max:255'];
        } elseif ($role === 'Admin') {
            $rules['admin_level'] = ['nullable', 'string', 'max:255'];
            $rules['access_scope'] = ['nullable', 'string', 'max:255'];
            $rules['admin_role'] = ['required', 'string', 'exists:roles,name'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($user, $validated, $role) {
            // Update user basic info
            $user->update([
                'name' => trim("{$validated['first_name']} {$validated['middle_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
            ]);

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
                $user->save();
            }

            // Update role
            if (method_exists($user, 'syncRoles')) {
                if ($role === 'Admin' && !empty($validated['admin_role'])) {
                    $user->syncRoles([$role, $validated['admin_role']]);
                } else {
                    $user->syncRoles([$role]);
                }
            }

            // Update or create profile
            $profileData = [
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
            ];

            // Get current role and handle profile change if needed
            $currentRole = $user->getRoleNames()->first();

            if ($currentRole !== $role) {
                // Role changed - delete old profile, create new one
                $this->deleteOldProfile($user);

                switch ($role) {
                    case 'Student':
                        $profile = Student::create(array_merge($profileData, [
                            'user_id' => $user->id,
                            'student_number' => $validated['student_number'],
                            'year_level' => $validated['year_level'] ?? null,
                            'program' => $validated['program'] ?? null,
                            'college' => $validated['college'] ?? null,
                            'enrollment_date' => now(),
                        ]));
                        break;

                    case 'Teacher':
                        $profile = Teacher::create(array_merge($profileData, [
                            'user_id' => $user->id,
                            'employee_id' => $validated['employee_id'],
                            'college' => $validated['college'] ?? null,
                            'program' => $validated['program'] ?? null,
                            'specialization' => $validated['specialization'] ?? null,
                            'hire_date' => now(),
                        ]));
                        break;

                    case 'Admin':
                        $profile = Admin::create(array_merge($profileData, [
                            'user_id' => $user->id,
                        ]));
                        break;
                }

                $user->update([
                    'profile_type' => get_class($profile),
                    'profile_id' => $profile->id,
                ]);
            } else {
                // Same role - just update existing profile
                $profile = $user->profile;
                if ($profile) {
                    switch ($role) {
                        case 'Student':
                            $profile->update(array_merge($profileData, [
                                'student_number' => $validated['student_number'],
                                'year_level' => $validated['year_level'] ?? $profile->year_level,
                                'program' => $validated['program'] ?? $profile->program,
                                'college' => $validated['college'] ?? $profile->college,
                            ]));
                            break;

                        case 'Teacher':
                            $profile->update(array_merge($profileData, [
                                'employee_id' => $validated['employee_id'],
                                'college' => $validated['college'] ?? $profile->college,
                                'program' => $validated['program'] ?? $profile->program,
                                'specialization' => $validated['specialization'] ?? $profile->specialization,
                            ]));
                            break;

                        case 'Admin':
                            $profile->update($profileData);
                            break;
                    }
                }
            }
        });

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    private function deleteOldProfile(User $user): void
    {
        if ($user->profile) {
            $user->profile->delete();
        }
        $user->student()?->delete();
        $user->teacher()?->delete();
        $user->admin()?->delete();
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
