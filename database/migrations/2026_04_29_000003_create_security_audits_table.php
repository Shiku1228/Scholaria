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
        Schema::create('security_audits', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // failed_login, suspicious_activity, security_breach, etc.
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->text('description');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->json('event_data')->nullable(); // Additional context data
            $table->string('fingerprint')->nullable(); // Device/browser fingerprint
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('requires_action')->default(false);
            $table->timestamp('action_taken_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['severity', 'is_resolved']);
            $table->index(['ip_address']);
            $table->index(['user_id']);
            $table->index(['requires_action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_audits');
    }
};
