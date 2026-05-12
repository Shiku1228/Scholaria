<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create granular admin roles
        $this->createGranularRoles();
        
        // Create granular permissions
        $this->createGranularPermissions();
        
        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function createGranularRoles(): void
    {
        // Check if roles already exist, only create new ones
        $existingRoles = DB::table('roles')->pluck('name')->toArray();
        
        $newRoles = [
            'Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'
        ];
        
        $rolesToCreate = array_diff($newRoles, $existingRoles);
        
        $roleData = [];
        foreach ($rolesToCreate as $roleName) {
            $roleData[] = [
                'name' => $roleName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($roleData)) {
            DB::table('roles')->insert($roleData);
        }
    }

    private function createGranularPermissions(): void
    {
        // Check if permissions already exist, only create new ones
        $existingPermissions = DB::table('permissions')->pluck('name')->toArray();
        
        $newPermissions = [
            'users.suspend', 'roles.manage', 'permissions.manage', 
            'settings.view', 'settings.edit', 'reports.view', 'reports.export'
        ];
        
        $permissionsToCreate = array_diff($newPermissions, $existingPermissions);
        
        $permissionData = [];
        foreach ($permissionsToCreate as $permissionName) {
            $permissionData[] = [
                'name' => $permissionName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($permissionData)) {
            DB::table('permissions')->insert($permissionData);
        }
    }

    private function assignPermissionsToRoles(): void
    {
        // Get role and permission IDs
        $roles = DB::table('roles')->pluck('id', 'name');
        $permissions = DB::table('permissions')->pluck('id', 'name');

        // Super Admin - All permissions
        $superAdminPermissions = array_values($permissions->toArray());
        foreach ($superAdminPermissions as $permissionId) {
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permissionId,
                'role_id' => $roles['Super Admin'],
            ]);
        }

        // Content Admin - Content permissions only
        $contentAdminPermissions = [
            'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
            'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
            'users.view', 'users.edit'
        ];
        foreach ($contentAdminPermissions as $permissionName) {
            if (isset($permissions[$permissionName])) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissions[$permissionName],
                    'role_id' => $roles['Content Admin'],
                ]);
            }
        }

        // User Admin - User management permissions only
        $userAdminPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.suspend'
        ];
        foreach ($userAdminPermissions as $permissionName) {
            if (isset($permissions[$permissionName])) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissions[$permissionName],
                    'role_id' => $roles['User Admin'],
                ]);
            }
        }

        // Report Admin - Reports only
        $reportAdminPermissions = [
            'reports.view', 'reports.export', 'users.view'
        ];
        foreach ($reportAdminPermissions as $permissionName) {
            if (isset($permissions[$permissionName])) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissions[$permissionName],
                    'role_id' => $roles['Report Admin'],
                ]);
            }
        }

        // Settings Admin - Settings and basic permissions
        $settingsAdminPermissions = [
            'settings.view', 'settings.edit', 'users.view'
        ];
        foreach ($settingsAdminPermissions as $permissionName) {
            if (isset($permissions[$permissionName])) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissions[$permissionName],
                    'role_id' => $roles['Settings Admin'],
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove the new granular roles and their permissions
        $roleNames = ['Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'];
        
        // Get role IDs
        $roleIds = DB::table('roles')->whereIn('name', $roleNames)->pluck('id');
        
        // Remove role_has_permissions for these roles
        DB::table('role_has_permissions')->whereIn('role_id', $roleIds)->delete();
        
        // Remove the roles
        DB::table('roles')->whereIn('name', $roleNames)->delete();
        
        // Remove granular permissions (keep existing ones if any)
        $permissionNames = [
            'users.suspend', 'roles.manage', 'permissions.manage', 
            'settings.view', 'settings.edit', 'reports.view', 'reports.export'
        ];
        DB::table('permissions')->whereIn('name', $permissionNames)->delete();
    }
};
