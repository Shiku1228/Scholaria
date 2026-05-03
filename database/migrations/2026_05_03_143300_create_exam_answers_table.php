<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exam_answers')) {
            Schema::create('exam_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attempt_id')->constrained('student_exam_attempts')->onDelete('cascade');
                $table->foreignId('question_id')->constrained('exam_questions')->onDelete('cascade');
                $table->text('answer'); // Student's answer
                $table->boolean('is_correct')->nullable(); // Auto-graded result
                $table->unsignedInteger('points_earned')->nullable();
                $table->text('feedback')->nullable(); // Teacher feedback
                $table->timestamps();

                $table->index(['attempt_id']);
                $table->index(['question_id']);
                $table->unique(['attempt_id', 'question_id']); // One answer per question per attempt
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};
