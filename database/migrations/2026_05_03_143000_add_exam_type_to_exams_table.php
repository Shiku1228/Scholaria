<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'exam_type')) {
                $table->enum('exam_type', ['scheduled', 'online'])->default('scheduled')->after('course_id');
            }
            if (!Schema::hasColumn('exams', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('instructions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'exam_type')) {
                $table->dropColumn('exam_type');
            }
            if (Schema::hasColumn('exams', 'is_published')) {
                $table->dropColumn('is_published');
            }
        });
    }
};
