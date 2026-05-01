<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create students table
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('student_number')->unique();
            $table->string('year_level')->nullable();
            $table->string('program')->nullable();
            $table->string('college')->nullable();
            $table->timestamp('enrollment_date')->nullable();
            $table->timestamps();
        });

        // Create teachers table
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('employee_id')->unique();
            $table->string('college')->nullable();
            $table->string('program')->nullable();
            $table->string('specialization')->nullable();
            $table->timestamp('hire_date')->nullable();
            $table->timestamps();
        });

        // Create admins table
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('admin_level')->default('standard');
            $table->string('access_scope')->default('all');
            $table->timestamps();
        });

        // Add profile morph columns to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profile_type')) {
                $table->string('profile_type')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'profile_id')) {
                $table->unsignedBigInteger('profile_id')->nullable()->after('profile_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'profile_type')) {
                $table->dropColumn('profile_type');
            }
            if (Schema::hasColumn('users', 'profile_id')) {
                $table->dropColumn('profile_id');
            }
        });

        Schema::dropIfExists('admins');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('students');
    }
};
