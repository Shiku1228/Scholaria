<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- quiz_answers: add auto_score + is_overridden ---
        if (Schema::hasTable('quiz_answers')) {
            Schema::table('quiz_answers', function (Blueprint $table) {
                if (!Schema::hasColumn('quiz_answers', 'auto_score')) {
                    $table->unsignedInteger('auto_score')->nullable()->after('points_earned');
                }
                if (!Schema::hasColumn('quiz_answers', 'is_overridden')) {
                    $table->boolean('is_overridden')->default(false)->after('auto_score');
                }
            });
            // Backfill: preserve original auto-computed scores
            DB::statement('UPDATE quiz_answers SET auto_score = points_earned WHERE auto_score IS NULL');
        }

        // --- quiz_attempts: add graded_at + graded_by + expand status enum ---
        if (Schema::hasTable('quiz_attempts')) {
            Schema::table('quiz_attempts', function (Blueprint $table) {
                if (!Schema::hasColumn('quiz_attempts', 'graded_at')) {
                    $table->timestamp('graded_at')->nullable()->after('submitted_at');
                }
                if (!Schema::hasColumn('quiz_attempts', 'graded_by')) {
                    $table->unsignedBigInteger('graded_by')->nullable()->after('graded_at');
                }
            });
            // Expand the status enum to include 'graded'
            DB::statement("ALTER TABLE quiz_attempts MODIFY COLUMN status ENUM('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quiz_attempts')) {
            DB::statement("ALTER TABLE quiz_attempts MODIFY COLUMN status ENUM('in_progress','submitted') NOT NULL DEFAULT 'in_progress'");
            Schema::table('quiz_attempts', function (Blueprint $table) {
                foreach (['graded_by', 'graded_at'] as $col) {
                    if (Schema::hasColumn('quiz_attempts', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('quiz_answers')) {
            Schema::table('quiz_answers', function (Blueprint $table) {
                foreach (['is_overridden', 'auto_score'] as $col) {
                    if (Schema::hasColumn('quiz_answers', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
