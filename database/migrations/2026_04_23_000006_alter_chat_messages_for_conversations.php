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
            if (!Schema::hasColumn('chat_messages', 'chat_conversation_id')) {
                $table->foreignId('chat_conversation_id')->nullable()->after('chat_group_id')->constrained('chat_conversations')->nullOnDelete();
            }
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->index(['chat_conversation_id', 'created_at'], 'chat_messages_conversation_created_at_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('chat_messages')) {
            return;
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('chat_messages', 'chat_conversation_id')) {
                $table->dropConstrainedForeignId('chat_conversation_id');
            }
        });
    }
};

