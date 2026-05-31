<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assignment_choices')) {
            Schema::create('assignment_choices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_id')
                    ->constrained('assignment_questions')
                    ->cascadeOnDelete();
                $table->text('choice_text');
                $table->boolean('is_correct')->default(false);
                $table->unsignedSmallInteger('order')->default(0);
                $table->timestamps();

                $table->index('question_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_choices');
    }
};
