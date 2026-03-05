<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('grades')) {
            Schema::create('grades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('users');
                $table->foreignId('assignment_id')->constrained('assignments');
                $table->unsignedInteger('score')->nullable();
                $table->text('feedback')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'assignment_id']);
                $table->index(['student_id']);
                $table->index(['assignment_id']);
            });

            return;
        }

        Schema::table('grades', function (Blueprint $table) {
            if (!Schema::hasColumn('grades', 'student_id')) {
                $table->foreignId('student_id')->nullable()->constrained('users')->after('id');
            }
            if (!Schema::hasColumn('grades', 'assignment_id')) {
                $table->foreignId('assignment_id')->nullable()->constrained('assignments')->after('student_id');
            }
            if (!Schema::hasColumn('grades', 'score')) {
                $table->unsignedInteger('score')->nullable()->after('assignment_id');
            }
            if (!Schema::hasColumn('grades', 'feedback')) {
                $table->text('feedback')->nullable()->after('score');
            }
            if (!Schema::hasColumn('grades', 'created_at')) {
                $table->timestamps();
            }
        });

        try {
            if (Schema::hasColumn('grades', 'student_id') && Schema::hasColumn('grades', 'assignment_id')) {
                Schema::table('grades', function (Blueprint $table) {
                    $table->unique(['student_id', 'assignment_id']);
                });
            }
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        // Intentionally left blank (safe migration)
    }
};
