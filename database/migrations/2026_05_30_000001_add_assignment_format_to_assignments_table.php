<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignments', 'assignment_format')) {
                $table->enum('assignment_format', ['essay', 'multiple_choice'])
                    ->default('essay')
                    ->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            if (Schema::hasColumn('assignments', 'assignment_format')) {
                $table->dropColumn('assignment_format');
            }
        });
    }
};
