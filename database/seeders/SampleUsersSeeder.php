<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Admin;
use App\Models\Teacher;
use App\Models\Student;
use Spatie\Permission\Models\Role;

class SampleUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample users for each role
        $this->createSampleAdminUsers();
        $this->createSampleTeacherUser();
        $this->createSampleStudentUser();
    }

    
    private function createSampleAdminUsers(): void
    {
        $adminUsers = [
            [
                'name' => 'Super Admin User',
                'email' => 'superadmin@scholaria.com',
                'role' => 'Super Admin',
                'first_name' => 'Super',
                'last_name' => 'Admin',
            ],
            [
                'name' => 'Content Admin User',
                'email' => 'contentadmin@scholaria.com',
                'role' => 'Content Admin',
                'first_name' => 'Content',
                'last_name' => 'Admin',
            ],
            [
                'name' => 'User Admin User',
                'email' => 'useradmin@scholaria.com',
                'role' => 'User Admin',
                'first_name' => 'User',
                'last_name' => 'Admin',
            ],
            [
                'name' => 'Report Admin User',
                'email' => 'reportadmin@scholaria.com',
                'role' => 'Report Admin',
                'first_name' => 'Report',
                'last_name' => 'Admin',
            ],
            [
                'name' => 'Settings Admin User',
                'email' => 'settingsadmin@scholaria.com',
                'role' => 'Settings Admin',
                'first_name' => 'Settings',
                'last_name' => 'Admin',
            ],
        ];

        foreach ($adminUsers as $adminData) {
            $user = User::updateOrCreate(
                ['email' => $adminData['email']],
                [
                    'name' => $adminData['name'],
                    'password' => Hash::make('password123'), // Default password for testing
                    'email_verified_at' => now(),
                ]
            );

            // Assign role
            $role = Role::where('name', $adminData['role'])->first();
            if ($role) {
                $user->syncRoles([$adminData['role']]);
            }

            // Create or update admin profile
            $admin = Admin::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $adminData['first_name'],
                    'last_name' => $adminData['last_name'],
                ]
            );

            // Update user profile references
            $user->update([
                'profile_type' => Admin::class,
                'profile_id' => $admin->id,
            ]);
        }
    }

    private function createSampleTeacherUser(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'teacher@scholaria.com'],
            [
                'name' => 'Teacher Sample',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Teacher role
        $teacherRole = Role::where('name', 'Teacher')->first();
        if ($teacherRole) {
            $user->syncRoles(['Teacher']);
        }

        // Create teacher profile
        $teacher = Teacher::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Teacher',
                'last_name' => 'Sample',
                'employee_id' => 'T001',
                'college' => 'College of Computer Studies',
                'program' => 'Bachelor of Science in Information Technology',
                'specialization' => 'Web Development',
                'hire_date' => now(),
            ]
        );

        // Update user profile references
        $user->update([
            'profile_type' => Teacher::class,
            'profile_id' => $teacher->id,
        ]);
    }

    private function createSampleStudentUser(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'student@scholaria.com'],
            [
                'name' => 'Student Sample',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Student role
        $studentRole = Role::where('name', 'Student')->first();
        if ($studentRole) {
            $user->syncRoles(['Student']);
        }

        // Create student profile
        $student = Student::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Student',
                'last_name' => 'Sample',
                'student_number' => 'S20240001',
                'year_level' => '3',
                'program' => 'Bachelor of Science in Information Technology',
                'college' => 'College of Computer Studies',
                'enrollment_date' => now(),
            ]
        );

        // Update user profile references
        $user->update([
            'profile_type' => Student::class,
            'profile_id' => $student->id,
        ]);
    }
}
