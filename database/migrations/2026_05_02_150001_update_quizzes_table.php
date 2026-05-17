<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'course_id')) {
                $table->foreignId('course_id')->nullable()->constrained('courses')->after('id');
            }
            if (!Schema::hasColumn('quizzes', 'title')) {
                $table->string('title')->nullable()->after('course_id');
            }
            if (!Schema::hasColumn('quizzes', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('quizzes', 'due_date')) {
                $table->timestamp('due_date')->nullable()->after('description');
            }
            if (!Schema::hasColumn('quizzes', 'max_score')) {
                $table->unsignedInteger('max_score')->default(100)->after('due_date');
            }
            if (!Schema::hasColumn('quizzes', 'time_limit')) {
                $table->unsignedInteger('time_limit')->default(60)->after('max_score');
            }
            if (!Schema::hasColumn('quizzes', 'attempts_allowed')) {
                $table->unsignedInteger('attempts_allowed')->default(1)->after('time_limit');
            }
            if (!Schema::hasColumn('quizzes', 'shuffle_questions')) {
                $table->boolean('shuffle_questions')->default(false)->after('attempts_allowed');
            }
            if (!Schema::hasColumn('quizzes', 'show_results')) {
                $table->boolean('show_results')->default(true)->after('shuffle_questions');
            }
            if (!Schema::hasColumn('quizzes', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('show_results');
            }
            if (!Schema::hasColumn('quizzes', 'points')) {
                $table->unsignedInteger('points')->default(0)->after('is_published');
            }
            if (!Schema::hasColumn('quizzes', 'created_at')) {
                $table->timestamps();
            }
            
            $table->index(['course_id']);
            $table->index(['due_date']);
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'course_id')) {
                $table->dropForeign(['course_id']);
                $table->dropColumn('course_id');
            }
            if (Schema::hasColumn('quizzes', 'title')) {
                $table->dropColumn('title');
            }
            if (Schema::hasColumn('quizzes', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('quizzes', 'due_date')) {
                $table->dropColumn('due_date');
            }
            if (Schema::hasColumn('quizzes', 'max_score')) {
                $table->dropColumn('max_score');
            }
            if (Schema::hasColumn('quizzes', 'time_limit')) {
                $table->dropColumn('time_limit');
            }
            if (Schema::hasColumn('quizzes', 'attempts_allowed')) {
                $table->dropColumn('attempts_allowed');
            }
            if (Schema::hasColumn('quizzes', 'shuffle_questions')) {
                $table->dropColumn('shuffle_questions');
            }
            if (Schema::hasColumn('quizzes', 'show_results')) {
                $table->dropColumn('show_results');
            }
            if (Schema::hasColumn('quizzes', 'is_published')) {
                $table->dropColumn('is_published');
            }
            if (Schema::hasColumn('quizzes', 'points')) {
                $table->dropColumn('points');
            }
        });
    }
};
