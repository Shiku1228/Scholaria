<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_discussions', function (Blueprint $table) {
            if (!Schema::hasColumn('course_discussions', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('user_id')->constrained('course_discussions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_discussions', function (Blueprint $table) {
            if (Schema::hasColumn('course_discussions', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }
        });
    }
};

