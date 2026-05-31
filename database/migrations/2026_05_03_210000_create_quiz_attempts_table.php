<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quiz_attempts')) {
            Schema::create('quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
                $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedInteger('score')->nullable();
                $table->enum('status', ['in_progress', 'submitted'])->default('in_progress');
                $table->timestamps();

                $table->index(['quiz_id']);
                $table->index(['student_id']);
                $table->unique(['quiz_id', 'student_id']); // One attempt per quiz per student
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
