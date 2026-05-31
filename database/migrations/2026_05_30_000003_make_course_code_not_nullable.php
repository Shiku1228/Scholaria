<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill nulls before enforcing NOT NULL
        DB::table('courses')->whereNull('course_code')->update(['course_code' => 'N/A']);

        Schema::table('courses', function (Blueprint $table) {
            $table->string('course_code', 50)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('course_code', 50)->nullable()->change();
        });
    }
};
