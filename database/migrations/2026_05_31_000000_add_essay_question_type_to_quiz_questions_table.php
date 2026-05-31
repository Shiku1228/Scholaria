<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quiz_questions') || !Schema::hasColumn('quiz_questions', 'question_type')) {
            return;
        }

        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("
            ALTER TABLE quiz_questions
            MODIFY question_type ENUM('multiple_choice', 'true_false', 'short_answer', 'essay')
            NOT NULL DEFAULT 'multiple_choice'
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('quiz_questions') || !Schema::hasColumn('quiz_questions', 'question_type')) {
            return;
        }

        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("
            ALTER TABLE quiz_questions
            MODIFY question_type ENUM('multiple_choice', 'true_false', 'short_answer')
            NOT NULL DEFAULT 'multiple_choice'
        ");
    }
};
