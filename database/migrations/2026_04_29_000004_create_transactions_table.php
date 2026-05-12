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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // UUID for external reference
            $table->string('type'); // create, update, delete, rollback, commit
            $table->enum('status', ['pending', 'committed', 'rolled_back', 'failed']);
            $table->string('table_name'); // The table being modified
            $table->unsignedBigInteger('record_id'); // The record being modified
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('data_before')->nullable(); // Before state
            $table->json('data_after')->nullable(); // After state
            $table->json('changed_fields')->nullable(); // List of modified fields
            $table->text('reason')->nullable(); // Reason for transaction
            $table->string('parent_transaction_id')->nullable(); // For nested transactions
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->boolean('requires_manual_review')->default(false);
            $table->timestamps();

            $table->index(['transaction_id']);
            $table->index(['type', 'status']);
            $table->index(['table_name', 'record_id']);
            $table->index(['user_id', 'started_at']);
            $table->index(['status', 'requires_manual_review']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
