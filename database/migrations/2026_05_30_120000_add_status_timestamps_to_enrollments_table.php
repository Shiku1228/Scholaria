<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('enrolled_at');
            }
            if (! Schema::hasColumn('enrollments', 'dropped_at')) {
                $table->timestamp('dropped_at')->nullable()->after('completed_at');
            }
            if (! Schema::hasColumn('enrollments', 'unenrolled_at')) {
                $table->timestamp('unenrolled_at')->nullable()->after('dropped_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('enrollments', 'completed_at'))  $cols[] = 'completed_at';
            if (Schema::hasColumn('enrollments', 'dropped_at'))    $cols[] = 'dropped_at';
            if (Schema::hasColumn('enrollments', 'unenrolled_at')) $cols[] = 'unenrolled_at';
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
