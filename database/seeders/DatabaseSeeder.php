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

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'Teacher',
                'role' => 'teacher',
                'password' => Hash::make('password'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Student',
                'role' => 'student',
                'password' => Hash::make('password'),
            ]
        );
    }
}
