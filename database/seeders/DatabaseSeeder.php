<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        $teacher = User::query()->updateOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'Teacher',
                'password' => Hash::make('password'),
            ]
        );

        $student = User::query()->updateOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Student',
                'password' => Hash::make('password'),
            ]
        );

        $this->call(RolesAndPermissionsSeeder::class);

        if (method_exists($admin, 'syncRoles')) {
            $admin->syncRoles(['Admin']);
        }
        if (method_exists($teacher, 'syncRoles')) {
            $teacher->syncRoles(['Teacher']);
        }
        if (method_exists($student, 'syncRoles')) {
            $student->syncRoles(['Student']);
        }
    }
}
