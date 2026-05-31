<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quiz_questions')) {
            Schema::create('quiz_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
                $table->text('question_text');
                $table->enum('question_type', ['multiple_choice', 'true_false', 'short_answer', 'essay'])->default('multiple_choice');
                $table->json('options')->nullable(); // For multiple choice: {"A": "Option A", "B": "Option B"}
                $table->text('correct_answer')->nullable(); // For auto-grading: "A", "true", etc.
                $table->unsignedInteger('points')->default(1);
                $table->unsignedInteger('order')->default(0);
                $table->timestamps();

                $table->index(['quiz_id']);
                $table->index(['order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
