<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chat_messages')) {
            return;
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_messages', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('is_system');
            }
            if (!Schema::hasColumn('chat_messages', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('edited_at');
            }
            if (!Schema::hasColumn('chat_messages', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('chat_messages')) {
            return;
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('chat_messages', 'deleted_by')) {
                $table->dropConstrainedForeignId('deleted_by');
            }
            if (Schema::hasColumn('chat_messages', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
            if (Schema::hasColumn('chat_messages', 'edited_at')) {
                $table->dropColumn('edited_at');
            }
        });
    }
};

