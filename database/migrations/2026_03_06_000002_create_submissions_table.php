<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('submissions')) {
            Schema::create('submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assignment_id')->constrained('assignments');
                $table->foreignId('student_id')->constrained('users');
                $table->string('file_path')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedInteger('score')->nullable();
                $table->text('feedback')->nullable();
                $table->timestamps();

                $table->unique(['assignment_id', 'student_id']);
                $table->index(['assignment_id']);
                $table->index(['student_id']);
                $table->index(['submitted_at']);
            });

            return;
        }

        Schema::table('submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('submissions', 'assignment_id')) {
                $table->foreignId('assignment_id')->nullable()->constrained('assignments')->after('id');
            }
            if (!Schema::hasColumn('submissions', 'student_id')) {
                $table->foreignId('student_id')->nullable()->constrained('users')->after('assignment_id');
            }
            if (!Schema::hasColumn('submissions', 'file_path')) {
                $table->string('file_path')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('submissions', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('submissions', 'score')) {
                $table->unsignedInteger('score')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('submissions', 'feedback')) {
                $table->text('feedback')->nullable()->after('score');
            }
            if (!Schema::hasColumn('submissions', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left blank (safe migration)
    }
};
