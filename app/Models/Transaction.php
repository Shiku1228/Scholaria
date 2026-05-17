<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Exception;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'type',
        'status',
        'table_name',
        'record_id',
        'user_id',
        'data_before',
        'data_after',
        'changed_fields',
        'reason',
        'parent_transaction_id',
        'started_at',
        'completed_at',
        'error_message',
        'retry_count',
        'requires_manual_review',
    ];

    protected $casts = [
        'data_before' => 'array',
        'data_after' => 'array',
        'changed_fields' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'requires_manual_review' => 'boolean',
        'retry_count' => 'integer',
    ];

    /**
     * Get the user who initiated the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent transaction (for nested transactions).
     */
    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }

    /**
     * Get child transactions.
     */
    public function childTransactions()
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id');
    }

    /**
     * Scope to get pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get failed transactions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get transactions requiring manual review.
     */
    public function scopeRequiresReview($query)
    {
        return $query->where('requires_manual_review', true);
    }

    /**
     * Scope to get transactions by table.
     */
    public function scopeForTable($query, string $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    /**
     * Scope to get transactions by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if the transaction can be retried.
     */
    public function canRetry(): bool
    {
        $maxRetries = config('security.transactions.retry_attempts', 3);
        return $this->status === 'failed' && $this->retry_count < $maxRetries;
    }

    /**
     * Mark transaction as committed.
     */
    public function commit(): bool
    {
        return $this->update([
            'status' => 'committed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark transaction as rolled back.
     */
    public function rollback(): bool
    {
        return $this->update([
            'status' => 'rolled_back',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed.
     */
    public function fail(string $errorMessage): bool
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Mark transaction as requiring manual review.
     */
    public function requireReview(): bool
    {
        return $this->update([
            'requires_manual_review' => true,
        ]);
    }

    /**
     * Create a new transaction.
     */
    public static function begin(array $data): self
    {
        return self::create([
            'transaction_id' => Str::uuid(),
            'type' => $data['type'],
            'status' => 'pending',
            'table_name' => $data['table_name'],
            'record_id' => $data['record_id'],
            'user_id' => $data['user_id'],
            'data_before' => $data['data_before'] ?? null,
            'data_after' => $data['data_after'] ?? null,
            'changed_fields' => $data['changed_fields'] ?? null,
            'reason' => $data['reason'] ?? null,
            'parent_transaction_id' => $data['parent_transaction_id'] ?? null,
            'started_at' => now(),
            'requires_manual_review' => $data['requires_manual_review'] ?? false,
        ]);
    }

    /**
     * Execute a transaction with rollback capability.
     */
    public static function execute(array $data, callable $operation): self
    {
        $transaction = self::begin($data);
        
        try {
            $result = $operation($transaction);
            
            if ($result) {
                $transaction->commit();
            } else {
                $transaction->rollback();
            }
            
            return $transaction;
        } catch (Exception $e) {
            $transaction->fail($e->getMessage());
            
            // Attempt rollback if possible
            try {
                if (method_exists($operation, 'rollback')) {
                    $operation->rollback();
                }
            } catch (Exception $rollbackException) {
                // Log rollback failure
                \Log::error('Transaction rollback failed', [
                    'transaction_id' => $transaction->transaction_id,
                    'error' => $rollbackException->getMessage(),
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Get changed fields between before and after data.
     */
    public static function getChangedFields(array $before, array $after): array
    {
        $changed = [];
        
        foreach ($after as $key => $value) {
            if (!array_key_exists($key, $before) || $before[$key] !== $value) {
                $changed[] = $key;
            }
        }
        
        return $changed;
    }

    /**
     * Check if transaction is stale (timeout).
     */
    public function isStale(): bool
    {
        $timeout = config('security.transactions.timeout_seconds', 30);
        return $this->status === 'pending' && 
               $this->started_at->diffInSeconds(now()) > $timeout;
    }

    /**
     * Get transaction duration.
     */
    public function getDuration(): ?int
    {
        if ($this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at);
        }
        
        return null;
    }
}
