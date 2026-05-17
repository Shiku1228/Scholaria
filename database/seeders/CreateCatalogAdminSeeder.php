<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateCatalogAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'catalogadmin@scholaria.com';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Catalog Admin User',
                'password' => Hash::make('password123'), // default testing password
                'email_verified_at' => now(),
            ]
        );

        // Ensure Catalog Admin role exists and assign it
        $role = Role::where('name', 'Catalog Admin')->first();
        if ($role) {
            $user->syncRoles(['Catalog Admin']);
        }

        // Create or update admin profile (matches SampleUsersSeeder pattern)
        $admin = Admin::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Catalog',
                'last_name' => 'Admin',
            ]
        );

        // Update user profile references
        $user->update([
            // legacy role column (used by some parts of the app/UI)
            'role' => 'admin',

            'profile_type' => Admin::class,
            'profile_id' => $admin->id,
        ]);
    }
}
