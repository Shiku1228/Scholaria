<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users');
            $table->foreignId('course_id')->constrained('courses');
            $table->foreignId('teacher_id')->constrained('users');
            $table->string('status', 20)->default('active');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();

            $table->unique(['student_id', 'course_id']);
            $table->index(['course_id']);
            $table->index(['teacher_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
