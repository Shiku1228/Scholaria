<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_exam_attempts')) {
            Schema::create('student_exam_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
                $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('started_at');
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedInteger('score')->nullable();
                $table->unsignedInteger('max_score');
                $table->enum('status', ['in_progress', 'submitted', 'graded'])->default('in_progress');
                $table->timestamps();

                $table->index(['exam_id']);
                $table->index(['student_id']);
                $table->unique(['exam_id', 'student_id']); // One attempt per exam per student
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_exam_attempts');
    }
};
