<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class GranularAdminRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Backup existing user-role assignments before clearing
        $backupUsers = DB::table('model_has_roles')
            ->whereIn('role_id', function($query) {
                $query->select('id')->from('roles')
                    ->whereIn('name', ['Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin']);
            })
            ->get(['model_id', 'model_type', 'role_id']);
            
        echo "Backed up " . count($backupUsers) . " user-role assignments\n";

        // Clear role-permission relationships for admin roles
        $adminRoleIds = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'])
            ->pluck('id');
            
        DB::table('role_has_permissions')->whereIn('role_id', $adminRoleIds)->delete();
        
        // Delete the roles
        DB::table('roles')->whereIn('name', ['Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'])->delete();

        // Create granular admin roles
        $roles = [
            'Super Admin' => 'Full system access with all permissions',
            'Content Admin' => 'Manage courses, lessons, and basic user operations',
            'User Admin' => 'Manage user accounts, roles, and permissions',
            'Report Admin' => 'View reports, analytics, and export data',
            'Settings Admin' => 'Manage system settings and configurations',
        ];

        foreach ($roles as $roleName => $description) {
            DB::table('roles')->insert([
                'name' => $roleName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create granular permissions (only if they don't exist)
        $newPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.suspend',
            'roles.manage', 'permissions.manage', 
            'settings.view', 'settings.edit', 
            'reports.view', 'reports.export',
            'courses.view', 'courses.create', 'courses.update', 'courses.delete',
            'lessons.view', 'lessons.create', 'lessons.update', 'lessons.delete'
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
            }
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
        
        // Restore user-role assignments
        $this->restoreUserRoleAssignments($backupUsers);
    }

    private function assignPermissionsToRoles(): void
    {
        // Get role IDs
        $roleIds = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'])
            ->pluck('id', 'name');
            
        $superAdminRoleId = $roleIds['Super Admin'];
        $contentAdminRoleId = $roleIds['Content Admin'];
        $userAdminRoleId = $roleIds['User Admin'];
        $reportAdminRoleId = $roleIds['Report Admin'];
        $settingsAdminRoleId = $roleIds['Settings Admin'];

        // Get all permission IDs
        $permissionIds = DB::table('permissions')->pluck('id', 'name');

        // Super Admin - All permissions
        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insert([
                'role_id' => $superAdminRoleId,
                'permission_id' => $permissionId,
            ]);
        }

        // Content Admin - Content and basic user permissions
        $contentAdminPermissions = [
            'users.view', 'users.edit',
            'courses.view', 'courses.create', 'courses.update', 'courses.delete',
            'lessons.view', 'lessons.create', 'lessons.update', 'lessons.delete',
        ];
        
        foreach ($contentAdminPermissions as $permissionName) {
            if (isset($permissionIds[$permissionName])) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $contentAdminRoleId,
                    'permission_id' => $permissionIds[$permissionName],
                ]);
            }
        }

        // User Admin - User management permissions
        $userAdminPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.suspend',
        ];
        
        foreach ($userAdminPermissions as $permissionName) {
            if (isset($permissionIds[$permissionName])) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $userAdminRoleId,
                    'permission_id' => $permissionIds[$permissionName],
                ]);
            }
        }

        // Report Admin - Reports and viewing permissions (read-only user access)
        $reportAdminPermissions = [
            'reports.view', 'reports.export', 'users.view',
        ];
        
        foreach ($reportAdminPermissions as $permissionName) {
            if (isset($permissionIds[$permissionName])) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $reportAdminRoleId,
                    'permission_id' => $permissionIds[$permissionName],
                ]);
            }
        }

        // Settings Admin - Settings and viewing permissions
        $settingsAdminPermissions = [
            'settings.view', 'settings.edit', 'users.view',
        ];
        
        foreach ($settingsAdminPermissions as $permissionName) {
            if (isset($permissionIds[$permissionName])) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $settingsAdminRoleId,
                    'permission_id' => $permissionIds[$permissionName],
                ]);
            }
        }
    }
    
    private function restoreUserRoleAssignments($backupUsers): void
    {
        // Get new role IDs
        $newRoleIds = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'])
            ->pluck('id', 'name')
            ->toArray();

        echo "Restoring user-role assignments...\n";

        foreach ($backupUsers as $backup) {
            // Map old role ID to new role ID based on role name
            $role = DB::table('roles')->where('id', $backup->role_id)->first();
            if ($role && isset($newRoleIds[$role->name])) {
                DB::table('model_has_roles')->insert([
                    'model_id' => $backup->model_id,
                    'model_type' => $backup->model_type,
                    'role_id' => $newRoleIds[$role->name],
                ]);
                echo "Restored user {$backup->model_id} to role {$role->name}\n";
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
