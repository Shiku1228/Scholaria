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
        Schema::table('submissions', function (Blueprint $table) {
            $table->enum('submission_type', ['text', 'file', 'link'])->default('file')->after('student_id');
            $table->text('content')->nullable()->after('submission_type');
            $table->index('submission_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['submission_type']);
            $table->dropColumn(['submission_type', 'content']);
        });
    }
};
