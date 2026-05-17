<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Closure;

class TransactionRecoveryService
{
    /**
     * Get all recoverable failed transactions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecoverableTransactions()
    {
        return Transaction::failed()
            ->where('retry_count', '<', config('security.transactions.retry_attempts', 3))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get stale pending transactions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStaleTransactions()
    {
        return Transaction::pending()
            ->get()
            ->filter(fn($t) => $t->isStale());
    }

    /**
     * Attempt to recover a failed transaction.
     *
     * @param Transaction $transaction The failed transaction to recover
     * @param Closure|null $recoveryOperation Optional custom recovery operation
     * @return Transaction
     * @throws Exception
     */
    public function recover(Transaction $transaction, ?Closure $recoveryOperation = null): Transaction
    {
        if (!$transaction->canRetry()) {
            throw new Exception('Transaction cannot be recovered: max retry attempts reached');
        }

        try {
            DB::beginTransaction();

            // Reset transaction to pending
            $transaction->update([
                'status' => 'pending',
                'error_message' => null,
                'started_at' => now(),
            ]);

            // Execute recovery operation
            if ($recoveryOperation) {
                $result = $recoveryOperation($transaction);
                
                if ($result === false) {
                    DB::rollBack();
                    $transaction->rollback();
                    return $transaction;
                }
            } else {
                // Default recovery: attempt to re-apply the data_after state
                $this->applyDefaultRecovery($transaction);
            }

            DB::commit();
            $transaction->commit();

            Log::info('Transaction recovered successfully', [
                'transaction_id' => $transaction->transaction_id,
                'retry_count' => $transaction->retry_count,
            ]);

            return $transaction;

        } catch (Exception $e) {
            DB::rollBack();
            $transaction->fail($e->getMessage());

            Log::error('Transaction recovery failed', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
                'retry_count' => $transaction->retry_count,
            ]);

            throw $e;
        }
    }

    /**
     * Apply default recovery logic based on transaction type.
     *
     * @param Transaction $transaction
     * @return void
     * @throws Exception
     */
    protected function applyDefaultRecovery(Transaction $transaction): void
    {
        $tableName = $transaction->table_name;
        $recordId = $transaction->record_id;
        $dataAfter = $transaction->data_after;

        if (!$dataAfter) {
            throw new Exception('No data_after available for recovery');
        }

        switch ($transaction->type) {
            case 'create':
                // Re-create the record
                DB::table($tableName)->insert($dataAfter);
                break;

            case 'update':
                // Re-apply the update
                DB::table($tableName)->where('id', $recordId)->update($dataAfter);
                break;

            case 'delete':
                // Re-delete the record
                DB::table($tableName)->where('id', $recordId)->delete();
                break;

            default:
                throw new Exception("Unsupported transaction type for recovery: {$transaction->type}");
        }
    }

    /**
     * Recover all recoverable transactions.
     *
     * @param Closure|null $recoveryOperation Optional custom recovery operation
     * @return array Recovery results
     */
    public function recoverAll(?Closure $recoveryOperation = null): array
    {
        $transactions = $this->getRecoverableTransactions();
        $results = [
            'total' => $transactions->count(),
            'recovered' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => [],
        ];

        foreach ($transactions as $transaction) {
            try {
                $this->recover($transaction, $recoveryOperation);
                $results['recovered']++;
                $results['details'][] = [
                    'transaction_id' => $transaction->transaction_id,
                    'status' => 'recovered',
                ];
            } catch (Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'transaction_id' => $transaction->transaction_id,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Batch transaction recovery completed', $results);

        return $results;
    }

    /**
     * Clean up stale pending transactions.
     *
     * @return int Number of transactions cleaned up
     */
    public function cleanupStaleTransactions(): int
    {
        $staleTransactions = $this->getStaleTransactions();
        $count = 0;

        foreach ($staleTransactions as $transaction) {
            try {
                $transaction->update([
                    'status' => 'failed',
                    'error_message' => 'Transaction timeout - marked as stale',
                    'completed_at' => now(),
                ]);
                $count++;
            } catch (Exception $e) {
                Log::error('Failed to mark stale transaction as failed', [
                    'transaction_id' => $transaction->transaction_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Cleaned up stale transactions', [
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Mark a transaction for manual review.
     *
     * @param Transaction $transaction
     * @param string $reason
     * @return bool
     */
    public function markForManualReview(Transaction $transaction, string $reason): bool
    {
        $result = $transaction->requireReview();
        
        Log::info('Transaction marked for manual review', [
            'transaction_id' => $transaction->transaction_id,
            'reason' => $reason,
        ]);

        return $result;
    }

    /**
     * Get recovery statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_failed' => Transaction::failed()->count(),
            'recoverable' => $this->getRecoverableTransactions()->count(),
            'stale' => $this->getStaleTransactions()->count(),
            'requires_review' => Transaction::requiresReview()->count(),
            'avg_retry_count' => Transaction::failed()->avg('retry_count') ?? 0,
        ];
    }

    /**
     * Rollback a transaction to its before state.
     *
     * @param Transaction $transaction
     * @return Transaction The rollback transaction
     * @throws Exception
     */
    public function rollbackToBeforeState(Transaction $transaction): Transaction
    {
        if (!$transaction->data_before) {
            throw new Exception('No data_before available for rollback');
        }

        try {
            DB::beginTransaction();

            $tableName = $transaction->table_name;
            $recordId = $transaction->record_id;
            $dataBefore = $transaction->data_before;

            // Apply the before state
            DB::table($tableName)->where('id', $recordId)->update($dataBefore);

            // Create rollback transaction record
            $rollbackTransaction = Transaction::begin([
                'type' => 'rollback',
                'table_name' => $tableName,
                'record_id' => $recordId,
                'user_id' => $transaction->user_id,
                'data_before' => $transaction->data_after,
                'data_after' => $dataBefore,
                'changed_fields' => $transaction->changed_fields,
                'reason' => "Manual rollback of transaction {$transaction->transaction_id}",
                'parent_transaction_id' => $transaction->transaction_id,
            ]);

            DB::commit();
            $rollbackTransaction->commit();

            Log::info('Transaction rolled back to before state', [
                'original_transaction_id' => $transaction->transaction_id,
                'rollback_transaction_id' => $rollbackTransaction->transaction_id,
            ]);

            return $rollbackTransaction;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to rollback transaction to before state', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get transaction recovery report.
     *
     * @return array
     */
    public function getRecoveryReport(): array
    {
        $stats = $this->getStatistics();
        $recoverable = $this->getRecoverableTransactions();
        $stale = $this->getStaleTransactions();

        return [
            'timestamp' => now()->toDateTimeString(),
            'statistics' => $stats,
            'recoverable_transactions' => $recoverable->map(fn($t) => [
                'transaction_id' => $t->transaction_id,
                'type' => $t->type,
                'table' => $t->table_name,
                'retry_count' => $t->retry_count,
                'error' => $t->error_message,
                'created_at' => $t->created_at->toDateTimeString(),
            ])->toArray(),
            'stale_transactions' => $stale->map(fn($t) => [
                'transaction_id' => $t->transaction_id,
                'type' => $t->type,
                'table' => $t->table_name,
                'started_at' => $t->started_at->toDateTimeString(),
                'duration_seconds' => $t->started_at->diffInSeconds(now()),
            ])->toArray(),
        ];
    }
}
