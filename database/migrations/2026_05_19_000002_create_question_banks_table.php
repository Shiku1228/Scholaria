<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('question_banks')) {
            Schema::create('question_banks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['teacher_id']);
            });
        }

        if (!Schema::hasTable('bank_questions')) {
            Schema::create('bank_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_bank_id')->constrained('question_banks')->onDelete('cascade');
                $table->text('question_text');
                $table->enum('question_type', ['multiple_choice', 'true_false', 'short_answer', 'essay'])->default('multiple_choice');
                $table->json('options')->nullable();
                $table->text('correct_answer')->nullable();
                $table->text('explanation')->nullable();
                $table->unsignedInteger('points')->default(1);
                $table->timestamps();

                $table->index(['question_bank_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_questions');
        Schema::dropIfExists('question_banks');
    }
};
