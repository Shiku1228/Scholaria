<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assignments')) {
            Schema::create('assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses');
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamp('due_date')->nullable();
                $table->unsignedInteger('max_score')->default(100);
                $table->timestamps();

                $table->index(['course_id']);
                $table->index(['due_date']);
            });

            return;
        }

        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignments', 'course_id')) {
                $table->foreignId('course_id')->nullable()->constrained('courses')->after('id');
            }
            if (!Schema::hasColumn('assignments', 'title')) {
                $table->string('title')->nullable()->after('course_id');
            }
            if (!Schema::hasColumn('assignments', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('assignments', 'due_date')) {
                $table->timestamp('due_date')->nullable()->after('description');
            }
            if (!Schema::hasColumn('assignments', 'max_score')) {
                $table->unsignedInteger('max_score')->default(100)->after('due_date');
            }
            if (!Schema::hasColumn('assignments', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left blank (safe migration)
    }
};
