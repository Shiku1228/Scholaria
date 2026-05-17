<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class GranularAdminRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear Spatie permission cache to ensure fresh state
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Backup existing user-role assignments before clearing
        $backupUsers = DB::table('model_has_roles')
            ->whereIn('role_id', function($query) {
                $query->select('id')->from('roles')
                    ->whereIn('name', ['Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin']);
            })
            ->get(['model_id', 'model_type', 'role_id']);
            
        echo "Backed up " . count($backupUsers) . " user-role assignments\n";
        
        // Get admin role IDs first (before deleting).
        // We include 'Admin' to clear legacy permissions that might cause leakage.
        $adminRoleIds = DB::table('roles')
            ->whereIn('name', ['Admin', 'Super Admin', 'Catalog Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'])
            ->pluck('id')
            ->toArray();

        // Only delete role-permission relationships (keep roles intact for now)
        if (!empty($adminRoleIds)) {
            DB::table('role_has_permissions')->whereIn('role_id', $adminRoleIds)->delete();
            echo "Cleared " . count($adminRoleIds) . " role permissions\n";
        }
        $roles = [
            'Super Admin' => 'Full system access with all permissions',
            'Catalog Admin' => 'Manage college/program catalog (colleges & programs)',
            'Content Admin' => 'Manage courses, lessons, and basic user operations',
            'User Admin' => 'Manage user accounts, roles, and permissions',
            'Report Admin' => 'View reports, analytics, and export data',
            'Settings Admin' => 'Manage system settings and configurations',
        ];

        foreach ($roles as $roleName => $description) {
            $exists = DB::table('roles')->where('name', $roleName)->exists();
            if (!$exists) {
                DB::table('roles')->insert([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                echo "Created role: $roleName\n";
            }
        }

        // Create granular permissions (only if they don't exist)
        $newPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.update', 'users.delete', 'users.suspend',
            'roles.manage', 'permissions.manage',
            'settings.view', 'settings.edit',
            'reports.view', 'reports.export',
            'courses.view', 'courses.create', 'courses.edit', 'courses.update', 'courses.delete',
            'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.update', 'lessons.delete',

            // Colleges & Programs Catalog
            'colleges.view', 'colleges.create', 'colleges.edit', 'colleges.update', 'colleges.delete',
            'programs.view', 'programs.create', 'programs.edit', 'programs.update', 'programs.delete',
        ];

        foreach ($newPermissions as $permissionName) {
            $existing = DB::table('permissions')->where('name', $permissionName)->first();
            if (!$existing) {
                DB::table('permissions')->insert([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                echo "Created permission: $permissionName\n";
            }
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
        
        // Restore user-role assignments
        $this->restoreUserRoleAssignments($backupUsers);

        echo "Seeder completed successfully!\n";
    }

    private function assignPermissionsToRoles(): void
    {
        // Get role IDs
        $roleIds = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'Catalog Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'])
            ->pluck('id', 'name')
            ->toArray();

        // Get all permission IDs keyed by name
        $permissionIds = DB::table('permissions')->pluck('id', 'name')->toArray();

        // Define granular permissions per role with all sidebar tabs they need
        $rolePermissionMap = [
            // Super Admin: Full system access - all permissions
            'Super Admin' => [
                // User Management
                'users.view', 'users.create', 'users.edit', 'users.update', 'users.delete', 'users.suspend',
                // Role & Permission Management
                'roles.manage', 'permissions.manage',
                // Content Management
                'courses.view', 'courses.create', 'courses.edit', 'courses.update', 'courses.delete',
                'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.update', 'lessons.delete',
                // Colleges & Programs (Catalog)
                'colleges.view', 'colleges.create', 'colleges.edit', 'colleges.update', 'colleges.delete',
                'programs.view', 'programs.create', 'programs.edit', 'programs.update', 'programs.delete',
                // Reports & Settings
                'reports.view', 'reports.export',
                'settings.view', 'settings.edit',
            ],

            // Catalog Admin: manage colleges & programs catalog
            'Catalog Admin' => [
                'colleges.view', 'colleges.create', 'colleges.edit', 'colleges.update', 'colleges.delete',
                'programs.view', 'programs.create', 'programs.edit', 'programs.update', 'programs.delete',
                // Often needs to view users for assignment pages; keep minimal
                'users.view',
            ],

            // Content Admin: Manage courses, lessons, and view/edit users only
            'Content Admin' => [
                // User Management (view & edit only)
                'users.view', 'users.edit', 'users.update',
                // Content Management (full access)
                'courses.view', 'courses.create', 'courses.edit', 'courses.update', 'courses.delete',
                'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.update', 'lessons.delete',
            ],

            // User Admin: Full user management only
            'User Admin' => [
                // User Management (full access)
                'users.view', 'users.create', 'users.edit', 'users.update', 'users.delete', 'users.suspend',
            ],

            // Report Admin: View reports and user data only (read-only)
            'Report Admin' => [
                // User Management (view only)
                'users.view',
                // Reports & Analytics (view & export)
                'reports.view', 'reports.export',
            ],

            // Settings Admin: Manage system settings and view users
            'Settings Admin' => [
                // User Management (view only)
                'users.view',
                // Settings (full access)
                'settings.view', 'settings.edit',
            ],
        ];

        foreach ($rolePermissionMap as $roleName => $permNames) {
            if (!isset($roleIds[$roleName])) {
                continue;
            }

            $roleId = $roleIds[$roleName];

            // Only insert permissions that don't already exist for this role
            foreach ($permNames as $permName) {
                if (!isset($permissionIds[$permName])) {
                    continue;
                }

                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionIds[$permName])
                    ->exists();

                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionIds[$permName],
                    ]);
                }
            }
        }
    }
    
    private function restoreUserRoleAssignments($backupUsers): void
    {
        // Get new role IDs
        $newRoleIds = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'Catalog Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'])
            ->pluck('id', 'name')
            ->toArray();

        echo "Restoring user-role assignments...\n";

        foreach ($backupUsers as $backup) {
            // Map old role ID to new role ID based on role name
            $role = DB::table('roles')->where('id', $backup->role_id)->first();
            if ($role && isset($newRoleIds[$role->name])) {
                $newRoleId = $newRoleIds[$role->name];
                
                // Check if this assignment already exists to avoid duplicates
                $exists = DB::table('model_has_roles')
                    ->where('model_id', $backup->model_id)
                    ->where('model_type', $backup->model_type)
                    ->where('role_id', $newRoleId)
                    ->exists();
                
                if (!$exists) {
                    DB::table('model_has_roles')->insert([
                        'model_id' => $backup->model_id,
                        'model_type' => $backup->model_type,
                        'role_id' => $newRoleId,
                    ]);
                    echo "Restored user {$backup->model_id} to role {$role->name}\n";
                } else {
                    echo "User {$backup->model_id} already has role {$role->name}, skipping...\n";
                }
            }
        }

        // Assign roles to users based on legacy role column if they don't have Spatie roles
        $this->assignRolesFromLegacyColumn($newRoleIds);
    }

    private function assignRolesFromLegacyColumn(array $newRoleIds): void
    {
        echo "Assigning roles from legacy role column...\n";

        // Check if legacy role column exists
        $hasLegacyColumn = Schema::hasColumn('users', 'role');

        if ($hasLegacyColumn) {
            // Get users who don't have any Spatie roles but have a legacy role
            $usersWithLegacyRole = DB::table('users')
                ->whereNotNull('role')
                ->where('role', '!=', '')
                ->whereNotIn('id', function($query) {
                    $query->select('model_id')
                        ->from('model_has_roles')
                        ->where('model_type', 'App\Models\User');
                })
                ->get();

            foreach ($usersWithLegacyRole as $user) {
                $legacyRole = strtolower($user->role);
                $roleName = null;

                // Map legacy role to new granular role
                if ($legacyRole === 'admin') {
                    $roleName = 'Super Admin'; // Legacy admin becomes Super Admin
                } elseif ($legacyRole === 'teacher') {
                    // Teacher is not in the admin roles, skip
                    continue;
                } elseif ($legacyRole === 'student') {
                    // Student is not in the admin roles, skip
                    continue;
                }

                if ($roleName && isset($newRoleIds[$roleName])) {
                    DB::table('model_has_roles')->insert([
                        'model_id' => $user->id,
                        'model_type' => 'App\Models\User',
                        'role_id' => $newRoleIds[$roleName],
                    ]);
                    echo "Assigned {$roleName} to user {$user->id} (legacy role: {$user->role})\n";
                }
            }
        }

        // Assign roles based on email patterns for users without any roles
        $this->assignRolesByEmailPattern($newRoleIds);
    }

    private function assignRolesByEmailPattern(array $newRoleIds): void
    {
        echo "Assigning roles based on email patterns...\n";

        // Email pattern to role mapping
        $emailRoleMap = [
            'superadmin@' => 'Super Admin',
            'contentadmin@' => 'Content Admin',
            'useradmin@' => 'User Admin',
            'reportadmin@' => 'Report Admin',
            'settingsadmin@' => 'Settings Admin',
        ];

        foreach ($emailRoleMap as $emailPattern => $roleName) {
            if (!isset($newRoleIds[$roleName])) {
                continue;
            }

            // Get users matching the email pattern who don't have any roles
            $users = DB::table('users')
                ->where('email', 'like', $emailPattern . '%')
                ->whereNotIn('id', function($query) {
                    $query->select('model_id')
                        ->from('model_has_roles')
                        ->where('model_type', 'App\Models\User');
                })
                ->get();

            foreach ($users as $user) {
                DB::table('model_has_roles')->insert([
                    'model_id' => $user->id,
                    'model_type' => 'App\Models\User',
                    'role_id' => $newRoleIds[$roleName],
                ]);
                echo "Assigned {$roleName} to user {$user->id} (email: {$user->email})\n";
            }
        }
    }
}
