<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleManagementController extends Controller
{
    public function index()
    {
        if (!Auth::user()->hasPermissionTo('roles.manage')) {
            abort(403, 'Unauthorized');
        }

        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function($permission) {
            if (str_contains($permission->name, 'users.')) return 'User Management';
            if (str_contains($permission->name, 'courses.') || str_contains($permission->name, 'lessons.')) return 'Content Management';
            return 'System Management';
        });

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function create()
    {
        if (!Auth::user()->hasPermissionTo('roles.manage')) {
            abort(403, 'Unauthorized');
        }

        $permissions = Permission::all()->groupBy(function($permission) {
            if (str_contains($permission->name, 'users.')) return 'User Management';
            if (str_contains($permission->name, 'courses.') || str_contains($permission->name, 'lessons.')) return 'Content Management';
            return 'System Management';
        });

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->hasPermissionTo('roles.manage')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        // Convert permission IDs to permission names
        $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
        $role->syncPermissions($permissionNames);

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->name}' created successfully.");
    }

    public function edit(Role $role)
    {
        if (!Auth::user()->hasPermissionTo('roles.manage')) {
            abort(403, 'Unauthorized');
        }

        $role->load('permissions');
        $permissions = Permission::all()->groupBy(function($permission) {
            if (str_contains($permission->name, 'users.')) return 'User Management';
            if (str_contains($permission->name, 'courses.') || str_contains($permission->name, 'lessons.')) return 'Content Management';
            return 'System Management';
        });

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        if (!Auth::user()->hasPermissionTo('roles.manage')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Additional validation to ensure all permission IDs actually exist
        $validPermissionIds = Permission::whereIn('id', $request->permissions)->pluck('id')->toArray();
        $invalidPermissions = array_diff($request->permissions, $validPermissionIds);
        if (!empty($invalidPermissions)) {
            return redirect()->back()
                ->with('error', 'Invalid permission IDs: ' . implode(', ', $invalidPermissions))
                ->withInput();
        }

        $role->update([
            'name' => $request->name,
        ]);

        // Convert permission IDs to permission names
        $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
        $role->syncPermissions($permissionNames);

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->name}' updated successfully.");
    }

    public function destroy(Role $role)
    {
        if (!Auth::user()->hasPermissionTo('roles.manage')) {
            abort(403, 'Unauthorized');
        }

        // Prevent deletion of roles that have users
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', "Cannot delete role '{$role->name}' as it has assigned users.");
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->name}' deleted successfully.");
    }

    public function assignRole(Request $request)
    {
        if (!Auth::user()->hasPermissionTo('users.edit')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $role = Role::findOrFail($request->role_id);

        $user->syncRoles([$role]);

        return redirect()->back()
            ->with('success', "User '{$user->name}' assigned to role '{$role->name}'.");
    }

    public function revokeRole(Request $request, User $user)
    {
        if (!Auth::user()->hasPermissionTo('users.edit')) {
            abort(403, 'Unauthorized');
        }

        $user->syncRoles([]);

        return redirect()->back()
            ->with('success', "All roles revoked from user '{$user->name}'.");
    }
}
