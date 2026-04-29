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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->nullable();
            $table->string('action'); // login, logout, create, update, delete, view, etc.
            $table->string('resource_type')->nullable(); // user, course, assignment, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->json('request_data')->nullable();
            $table->text('encrypted_data')->nullable(); // For sensitive information
            $table->string('encryption_key')->nullable(); // Key identifier for encrypted data
            $table->boolean('is_sensitive')->default(false);
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index(['ip_address']);
            $table->index(['is_sensitive']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
