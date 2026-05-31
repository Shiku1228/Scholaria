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
        Schema::table('assignment_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('assignment_answers', 'feedback')) {
                $table->text('feedback')->nullable()->after('score');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assignment_answers', function (Blueprint $table) {
            if (Schema::hasColumn('assignment_answers', 'feedback')) {
                $table->dropColumn('feedback');
            }
        });
    }
};
