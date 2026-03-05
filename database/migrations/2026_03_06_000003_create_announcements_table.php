<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses');
                $table->foreignId('teacher_id')->constrained('users');
                $table->string('title');
                $table->text('message');
                $table->timestamps();

                $table->index(['course_id']);
                $table->index(['teacher_id']);
                $table->index(['created_at']);
            });

            return;
        }

        Schema::table('announcements', function (Blueprint $table) {
            if (!Schema::hasColumn('announcements', 'course_id')) {
                $table->foreignId('course_id')->nullable()->constrained('courses')->after('id');
            }
            if (!Schema::hasColumn('announcements', 'teacher_id')) {
                $table->foreignId('teacher_id')->nullable()->constrained('users')->after('course_id');
            }
            if (!Schema::hasColumn('announcements', 'title')) {
                $table->string('title')->nullable()->after('teacher_id');
            }
            if (!Schema::hasColumn('announcements', 'message')) {
                $table->text('message')->nullable()->after('title');
            }
            if (!Schema::hasColumn('announcements', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left blank (safe migration)
    }
};
