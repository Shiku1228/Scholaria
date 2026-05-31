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
            if (!Schema::hasColumn('submissions', 'graded_by')) {
                $table->foreignId('graded_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('feedback');
            }
            if (!Schema::hasColumn('submissions', 'graded_at')) {
                $table->timestamp('graded_at')->nullable()->after('graded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'graded_by')) {
                $table->dropForeign(['graded_by']);
                $table->dropColumn('graded_by');
            }
            if (Schema::hasColumn('submissions', 'graded_at')) {
                $table->dropColumn('graded_at');
            }
        });
    }
};
