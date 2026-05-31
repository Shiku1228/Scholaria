<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. quiz_attempts updates
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropUnique('quiz_attempts_quiz_id_student_id_unique');
            
            $table->unsignedInteger('attempt_number')->default(1)->after('student_id');
            $table->json('question_ids')->nullable()->after('status');
        });

        // 2. student_exam_attempts updates
        Schema::table('student_exam_attempts', function (Blueprint $table) {
            $table->dropUnique('student_exam_attempts_exam_id_student_id_unique');
            
            $table->unsignedInteger('attempt_number')->default(1)->after('student_id');
            $table->json('question_ids')->nullable()->after('status');
        });

        // 3. quizzes updates
        Schema::table('quizzes', function (Blueprint $table) {
            $table->timestamp('start_date')->nullable()->after('description');
            $table->enum('feedback_type', ['instant', 'delayed'])->default('instant')->after('show_results');
            $table->boolean('results_released')->default(false)->after('feedback_type');
            $table->unsignedInteger('random_subset_count')->nullable()->after('shuffle_questions');
        });

        // 4. exams updates
        Schema::table('exams', function (Blueprint $table) {
            $table->dateTime('due_date')->nullable()->after('exam_date');
            $table->unsignedInteger('attempts_allowed')->default(1)->after('duration');
            $table->enum('feedback_type', ['instant', 'delayed'])->default('instant')->after('instructions');
            $table->boolean('results_released')->default(false)->after('feedback_type');
            $table->boolean('show_results')->default(true)->after('results_released');
            $table->boolean('shuffle_questions')->default(false)->after('show_results');
            $table->unsignedInteger('random_subset_count')->nullable()->after('shuffle_questions');
        });

        // 5. quiz_questions & exam_questions updates
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->text('explanation')->nullable()->after('correct_answer');
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->text('explanation')->nullable()->after('correct_answer');
        });
    }

    public function down(): void
    {
        // Revert quiz_attempts
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn(['attempt_number', 'question_ids']);
            $table->unique(['quiz_id', 'student_id']);
        });

        // Revert student_exam_attempts
        Schema::table('student_exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['attempt_number', 'question_ids']);
            $table->unique(['exam_id', 'student_id']);
        });

        // Revert quizzes
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'feedback_type', 'results_released', 'random_subset_count']);
        });

        // Revert exams
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'attempts_allowed', 'feedback_type', 'results_released', 'show_results', 'shuffle_questions', 'random_subset_count']);
        });

        // Revert questions
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('explanation');
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropColumn('explanation');
        });
    }
};
