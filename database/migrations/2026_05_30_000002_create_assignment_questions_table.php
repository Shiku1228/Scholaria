<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assignment_questions')) {
            Schema::create('assignment_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
                $table->text('question_text');
                $table->unsignedInteger('points')->default(1);
                $table->unsignedSmallInteger('order')->default(0);
                $table->timestamps();

                $table->index('assignment_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_questions');
    }
};
