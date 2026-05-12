<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateProfiles extends Command
{
    protected $signature = 'profiles:migrate {--force : Skip confirmation}';

    protected $description = 'Migrate existing user data to profile tables';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('This will migrate user data to profile tables. Continue?')) {
            return 1;
        }

        $this->info('Starting profile migration...');

        if (!Schema::hasTable('students') || !Schema::hasTable('teachers') || !Schema::hasTable('admins')) {
            $this->error('Profile tables do not exist. Please run migrations first.');
            return 1;
        }

        $stats = [
            'students' => 0,
            'teachers' => 0,
            'admins' => 0,
            'skipped' => 0,
        ];

        User::chunk(100, function ($users) use (&$stats) {
            foreach ($users as $user) {
                // Skip if already has profile
                if ($user->profile_id !== null) {
                    $stats['skipped']++;
                    continue;
                }

                $role = $this->getUserRole($user);

                match ($role) {
                    'Student' => $this->migrateStudent($user, $stats),
                    'Teacher' => $this->migrateTeacher($user, $stats),
                    'Admin' => $this->migrateAdmin($user, $stats),
                    default => $stats['skipped']++,
                };
            }
        });

        $this->info('Migration complete!');
        $this->table(['Type', 'Count'], [
            ['Students migrated', $stats['students']],
            ['Teachers migrated', $stats['teachers']],
            ['Admins migrated', $stats['admins']],
            ['Skipped (already migrated)', $stats['skipped']],
        ]);

        return 0;
    }

    private function getUserRole(User $user): ?string
    {
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('Student')) return 'Student';
            if ($user->hasRole('Teacher')) return 'Teacher';
            if ($user->hasRole('Admin')) return 'Admin';
        }

        // Fallback to legacy role column
        if (Schema::hasColumn('users', 'role')) {
            return match (strtolower($user->getAttribute('role') ?? '')) {
                'student' => 'Student',
                'teacher' => 'Teacher',
                'admin' => 'Admin',
                default => null,
            };
        }

        return null;
    }

    private function migrateStudent(User $user, array &$stats): void
    {
        try {
            $firstName = $user->first_name ?? '';
            $middleName = $user->middle_name ?? null;
            $lastName = $user->last_name ?? '';

            // If no split names, extract from name field
            if (empty($firstName) && empty($lastName)) {
                $parts = explode(' ', $user->name, 2);
                $firstName = $parts[0] ?? '';
                $lastName = $parts[1] ?? '';
            }

            $student = Student::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'student_number' => $user->student_number ?? $this->generateStudentNumber($user->id),
                'year_level' => null,
                'program' => null,
                'college' => null,
                'enrollment_date' => now(),
            ]);

            $user->update([
                'profile_type' => Student::class,
                'profile_id' => $student->id,
            ]);

            $stats['students']++;
        } catch (\Throwable $e) {
            $this->error("Failed to migrate student {$user->id}: {$e->getMessage()}");
        }
    }

    private function migrateTeacher(User $user, array &$stats): void
    {
        try {
            $firstName = $user->first_name ?? '';
            $middleName = $user->middle_name ?? null;
            $lastName = $user->last_name ?? '';

            if (empty($firstName) && empty($lastName)) {
                $parts = explode(' ', $user->name, 2);
                $firstName = $parts[0] ?? '';
                $lastName = $parts[1] ?? '';
            }

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'employee_id' => $this->generateEmployeeNumber($user->id),
                'college' => null,
                'program' => null,
                'specialization' => null,
                'hire_date' => now(),
            ]);

            $user->update([
                'profile_type' => Teacher::class,
                'profile_id' => $teacher->id,
            ]);

            $stats['teachers']++;
        } catch (\Throwable $e) {
            $this->error("Failed to migrate teacher {$user->id}: {$e->getMessage()}");
        }
    }

    private function migrateAdmin(User $user, array &$stats): void
    {
        try {
            $firstName = $user->first_name ?? '';
            $middleName = $user->middle_name ?? null;
            $lastName = $user->last_name ?? '';

            if (empty($firstName) && empty($lastName)) {
                $parts = explode(' ', $user->name, 2);
                $firstName = $parts[0] ?? '';
                $lastName = $parts[1] ?? '';
            }

            $admin = Admin::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'admin_level' => 'standard',
                'access_scope' => 'all',
            ]);

            $user->update([
                'profile_type' => Admin::class,
                'profile_id' => $admin->id,
            ]);

            $stats['admins']++;
        } catch (\Throwable $e) {
            $this->error("Failed to migrate admin {$user->id}: {$e->getMessage()}");
        }
    }

    private function generateStudentNumber(int $userId): string
    {
        return 'STU-' . str_pad($userId, 6, '0', STR_PAD_LEFT);
    }

    private function generateEmployeeNumber(int $userId): string
    {
        return 'EMP-' . str_pad($userId, 6, '0', STR_PAD_LEFT);
    }
}
