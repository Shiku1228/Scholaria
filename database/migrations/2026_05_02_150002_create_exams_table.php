<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exams')) {
            Schema::create('exams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses');
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('exam_date');
                $table->unsignedInteger('duration')->default(120); // in minutes
                $table->unsignedInteger('max_score')->default(100);
                $table->string('location')->nullable();
                $table->text('instructions')->nullable();
                $table->timestamps();

                $table->index(['course_id']);
                $table->index(['exam_date']);
            });

            return;
        }

        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'course_id')) {
                $table->foreignId('course_id')->nullable()->constrained('courses')->after('id');
            }
            if (!Schema::hasColumn('exams', 'title')) {
                $table->string('title')->nullable()->after('course_id');
            }
            if (!Schema::hasColumn('exams', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('exams', 'exam_date')) {
                $table->dateTime('exam_date')->nullable()->after('description');
            }
            if (!Schema::hasColumn('exams', 'duration')) {
                $table->unsignedInteger('duration')->default(120)->after('exam_date');
            }
            if (!Schema::hasColumn('exams', 'max_score')) {
                $table->unsignedInteger('max_score')->default(100)->after('duration');
            }
            if (!Schema::hasColumn('exams', 'location')) {
                $table->string('location')->nullable()->after('max_score');
            }
            if (!Schema::hasColumn('exams', 'instructions')) {
                $table->text('instructions')->nullable()->after('location');
            }
            if (!Schema::hasColumn('exams', 'created_at')) {
                $table->timestamps();
            }
            
            $table->index(['course_id']);
            $table->index(['exam_date']);
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'course_id')) {
                $table->dropForeign(['course_id']);
                $table->dropColumn('course_id');
            }
            if (Schema::hasColumn('exams', 'title')) {
                $table->dropColumn('title');
            }
            if (Schema::hasColumn('exams', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('exams', 'exam_date')) {
                $table->dropColumn('exam_date');
            }
            if (Schema::hasColumn('exams', 'duration')) {
                $table->dropColumn('duration');
            }
            if (Schema::hasColumn('exams', 'max_score')) {
                $table->dropColumn('max_score');
            }
            if (Schema::hasColumn('exams', 'location')) {
                $table->dropColumn('location');
            }
            if (Schema::hasColumn('exams', 'instructions')) {
                $table->dropColumn('instructions');
            }
        });
    }
};
