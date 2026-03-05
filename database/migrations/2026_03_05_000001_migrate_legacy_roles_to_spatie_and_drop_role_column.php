<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            User::withTrashed()
                ->select(['id', 'role'])
                ->chunkById(200, function ($users): void {
                    foreach ($users as $user) {
                        $legacy = strtolower((string) ($user->role ?? ''));

                        $mapped = match ($legacy) {
                            'admin' => 'Admin',
                            'teacher' => 'Editor',
                            'student' => 'User',
                            default => null,
                        };

                        if ($mapped !== null && method_exists($user, 'hasRole') && method_exists($user, 'assignRole')) {
                            if (!$user->hasRole($mapped)) {
                                $user->assignRole($mapped);
                            }
                        }
                    }
                });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('student')->after('password');
            }
        });
    }
};
