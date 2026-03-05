<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->string('course_number', 50)->unique();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('semester', 20);
                $table->string('school_year', 20)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('days_pattern', 50)->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->foreignId('teacher_id')->constrained('users');
                $table->timestamps();
            });

            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'course_number')) {
                $table->string('course_number', 50)->after('id');
            }

            if (!Schema::hasColumn('courses', 'title')) {
                $table->string('title')->nullable()->after('course_number');
            }

            if (!Schema::hasColumn('courses', 'description')) {
                $table->text('description')->nullable()->after('title');
            }

            if (!Schema::hasColumn('courses', 'semester')) {
                $table->string('semester', 20)->nullable()->after('description');
            }

            if (!Schema::hasColumn('courses', 'school_year')) {
                $table->string('school_year', 20)->nullable()->after('semester');
            }

            if (!Schema::hasColumn('courses', 'start_date')) {
                $table->date('start_date')->nullable()->after('school_year');
            }

            if (!Schema::hasColumn('courses', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }

            if (!Schema::hasColumn('courses', 'days_pattern')) {
                $table->string('days_pattern', 50)->nullable()->after('end_date');
            }

            if (!Schema::hasColumn('courses', 'start_time')) {
                $table->time('start_time')->nullable()->after('days_pattern');
            }

            if (!Schema::hasColumn('courses', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }

            if (!Schema::hasColumn('courses', 'teacher_id')) {
                $table->foreignId('teacher_id')->nullable()->constrained('users')->after('end_time');
            }
        });

        try {
            $col = DB::selectOne("SHOW COLUMNS FROM courses WHERE Field = 'semester'");
            $type = is_object($col) ? ($col->Type ?? null) : null;

            if (is_string($type) && str_starts_with(strtolower($type), 'enum(')) {
                $lower = strtolower($type);
                if (!str_contains($lower, "'summer'")) {
                    DB::statement("ALTER TABLE courses MODIFY semester ENUM('first','second','summer') NOT NULL");
                }
            }
        } catch (\Throwable) {
        }

        try {
            DB::statement('ALTER TABLE courses ADD UNIQUE KEY courses_course_number_unique (course_number)');
        } catch (\Throwable) {
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
