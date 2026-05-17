<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exam_questions')) {
            Schema::create('exam_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
                $table->text('question_text');
                $table->enum('question_type', ['multiple_choice', 'true_false', 'short_answer', 'essay'])->default('multiple_choice');
                $table->json('options')->nullable(); // For multiple choice: ["A", "B", "C", "D"]
                $table->text('correct_answer')->nullable(); // For auto-grading: "A", "true", etc.
                $table->unsignedInteger('points')->default(1);
                $table->unsignedInteger('order')->default(0);
                $table->timestamps();

                $table->index(['exam_id']);
                $table->index(['order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
