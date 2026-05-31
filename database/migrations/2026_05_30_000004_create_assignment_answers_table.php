<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assignment_answers')) {
            Schema::create('assignment_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
                $table->foreignId('question_id')
                    ->constrained('assignment_questions')
                    ->cascadeOnDelete();
                $table->foreignId('selected_choice_id')
                    ->nullable()
                    ->constrained('assignment_choices')
                    ->nullOnDelete();
                $table->text('essay_answer')->nullable();
                $table->unsignedInteger('score')->nullable();
                $table->timestamps();

                $table->unique(['submission_id', 'question_id']);
                $table->index('submission_id');
                $table->index('question_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_answers');
    }
};
