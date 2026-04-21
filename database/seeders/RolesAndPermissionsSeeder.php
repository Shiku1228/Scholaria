<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->migrateLegacyRoleNames();

        $permissions = [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.manage',
            'permissions.manage',
            'courses.view',
            'courses.create',
            'courses.update',
            'courses.delete',
            'lessons.view',
            'lessons.create',
            'lessons.update',
            'lessons.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('Admin', 'web');
        $teacherRole = Role::findOrCreate('Teacher', 'web');
        $studentRole = Role::findOrCreate('Student', 'web');

        $adminRole->syncPermissions($permissions);

        $teacherRole->syncPermissions([
            'courses.view',
            'courses.create',
            'courses.update',
            'courses.delete',
            'lessons.view',
            'lessons.create',
            'lessons.update',
            'lessons.delete',
        ]);

        $studentRole->syncPermissions([
            'courses.view',
            'lessons.view',
        ]);
    }

    private function migrateLegacyRoleNames(): void
    {
        $this->renameOrMergeRole('Editor', 'Teacher', 'web');
        $this->renameOrMergeRole('User', 'Student', 'web');
    }

    private function renameOrMergeRole(string $fromName, string $toName, string $guardName): void
    {
        $fromRole = Role::query()
            ->where('name', $fromName)
            ->where('guard_name', $guardName)
            ->first();

        if (!$fromRole) {
            return;
        }

        $toRole = Role::query()
            ->where('name', $toName)
            ->where('guard_name', $guardName)
            ->first();

        if (!$toRole) {
            $fromRole->name = $toName;
            $fromRole->save();
            return;
        }

        DB::table('model_has_roles')
            ->where('role_id', $fromRole->id)
            ->update(['role_id' => $toRole->id]);

        $stillUsed = DB::table('model_has_roles')
            ->where('role_id', $fromRole->id)
            ->exists();

        if (!$stillUsed) {
            $fromRole->delete();
        }
    }
}
