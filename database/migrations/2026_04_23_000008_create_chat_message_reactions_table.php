<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('emoji', 10);
            $table->timestamps();

            $table->unique(['chat_message_id', 'user_id', 'emoji'], 'chat_msg_reactions_unique');
            $table->index(['chat_message_id', 'created_at'], 'chat_msg_reactions_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_message_reactions');
    }
};

