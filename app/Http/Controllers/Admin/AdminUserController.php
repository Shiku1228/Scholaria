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
    public function __construct()
    {
        // Enforce granular permissions per action
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.create')->only(['create', 'store']);
        $this->middleware('permission:users.edit')->only(['edit', 'update']);
        $this->middleware('permission:users.delete')->only(['destroy', 'restore']);
    }

    private const ADMIN_ROLE_OPTIONS = [
        'Super Admin',
        'Content Admin',
        'User Admin',
        'Report Admin',
        'Settings Admin',
    ];

    // NOTE:
    // Do not restrict admin roles to a hardcoded list, because admins may add new granular
    // roles in the database. Any role that is not Teacher/Student is treated as an Admin role
    // and mapped into the existing Admin profile fields.

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

        if ($role === 'Admin') {
            // Query all granular admin roles too
            $adminRoleNames = array_merge(['Admin'], self::ADMIN_ROLE_OPTIONS);

            $query->whereHas('roles', function ($roleQuery) use ($adminRoleNames) {
                $roleQuery->whereIn('name', $adminRoleNames);
            });
        } elseif (in_array($role, ['Teacher', 'Student'], true)) {
            $query->role($role);
        } else {
            // Allow direct filtering by granular role names if provided
            if (in_array($role, self::ADMIN_ROLE_OPTIONS, true)) {
                $query->whereHas('roles', function ($roleQuery) use ($role) {
                    $roleQuery->whereIn('name', [$role]);
                });
            }
        }

        // Load profile relationships for search
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('email', 'like', '%' . $search . '%');

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
            ->with(['student', 'teacher', 'admin', 'roles'])
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
        // Load roles from Spatie so the UI reflects what's in DB.
        $roles = \Spatie\Permission\Models\Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

        $adminRoleNames = $roles
            ->pluck('name')
            ->reject(fn ($name) => $name === 'Teacher' || $name === 'Student')
            ->values()
            ->all();

        return view('admin.users.create', [
            'roles' => $roles,
            'adminRoleNames' => $adminRoleNames,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $role = (string) $request->input('role');

        $availableRoles = \Spatie\Permission\Models\Role::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in($availableRoles)],
        ];

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
        } else {
            // Admin roles (including any custom granular admin role)
            $rules['admin_level'] = ['nullable', 'string', 'max:255'];
            $rules['access_scope'] = ['nullable', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $role) {
            $user = User::create([
                'name' => trim("{$validated['first_name']} {$validated['middle_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([$role]);
            }

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

                default:
                    if ($this->isAdminRole($role)) {
                        // admins table no longer has admin_level/access_scope (dropped in migration)
                        $profile = Admin::create($profileData);
                        break;
                    }

                    abort(400, 'Unsupported role for profile creation.');
            }

            $user->update([
                'profile_type' => get_class($profile),
                'profile_id' => $profile->id,
            ]);
        });

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit($user): View
    {
        $user = User::withTrashed()
            ->with(['student', 'teacher', 'admin'])
            ->findOrFail($user);

        return view('admin.users.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, $user): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($user);
        $role = (string) $request->input('role');

        $availableRoles = \Spatie\Permission\Models\Role::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in($availableRoles)],
        ];

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
        } else {
            $rules['admin_level'] = ['nullable', 'string', 'max:255'];
            $rules['access_scope'] = ['nullable', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($user, $validated, $role) {
            $user->update([
                'name' => trim("{$validated['first_name']} {$validated['middle_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
            ]);

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
                $user->save();
            }

            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([$role]);
            }

            $profileData = [
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
            ];

            $currentRole = $user->getRoleNames()->first();

            if ($currentRole !== $role) {
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

                    default:
                        if ($this->isAdminRole($role)) {
                            $adminMeta = $this->mapAdminRoleToMeta($role);

                            $profile = Admin::create(array_merge($profileData, [
                                'user_id' => $user->id,
                                'admin_level' => $adminMeta['admin_level'],
                                'access_scope' => $adminMeta['access_scope'],
                            ]));
                            break;
                        }

                        abort(400, 'Unsupported role for profile creation.');
                }

                $user->update([
                    'profile_type' => get_class($profile),
                    'profile_id' => $profile->id,
                ]);
            } else {
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

                        default:
                            if ($this->isAdminRole($role) && $profile instanceof Admin) {
                                // admins table no longer has admin_level/access_scope (dropped in migration)
                                $profile->update($profileData);
                            } else {
                                abort(400, 'Unsupported role for profile update.');
                            }
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

    private function isAdminRole(string $role): bool
    {
        // Any role that is not Teacher/Student is considered an Admin role for profile mapping.
        return $role !== 'Teacher' && $role !== 'Student';
    }

    private function mapAdminRoleToMeta(string $adminRole): array
    {
        // Map granular admin roles into the existing Admin profile fields.
        return match ($adminRole) {
            'Super Admin' => ['admin_level' => 'super', 'access_scope' => 'all'],
            default => ['admin_level' => 'standard', 'access_scope' => 'limited'],
        };
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

        if (method_exists($user, 'hasAnyRole')) {
            $protectedRoles = array_merge(['Admin'], self::ADMIN_ROLE_OPTIONS);
            if ($user->hasAnyRole($protectedRoles)) {
                abort(403);
            }
        }

        if (!$user->trashed()) {
            $user->delete();
        }

        return redirect()->back();
    }

    public function restore($user): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($user);

        if (method_exists($user, 'hasAnyRole')) {
            $protectedRoles = array_merge(['Admin'], self::ADMIN_ROLE_OPTIONS);
            if ($user->hasAnyRole($protectedRoles)) {
                abort(403);
            }
        }

        if ($user->trashed()) {
            $user->restore();
        }

        return redirect()->route('admin.users.index');
    }
}
