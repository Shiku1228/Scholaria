<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Closure;

class TransactionService
{
    /**
     * Execute a transaction with ACID compliance and automatic rollback.
     *
     * @param array $transactionData Transaction metadata
     * @param Closure $operation The database operation to execute
     * @return Transaction
     * @throws Exception
     */
    public function execute(array $transactionData, Closure $operation): Transaction
    {
        $transaction = null;

        try {
            DB::beginTransaction();

            // Create transaction record
            $transaction = Transaction::begin($transactionData);

            // Execute the operation
            $result = $operation($transaction);

            if ($result === false) {
                // Operation returned false, rollback
                DB::rollBack();
                $transaction->rollback();
                Log::warning('Transaction rolled back by operation', [
                    'transaction_id' => $transaction->transaction_id,
                    'type' => $transaction->type,
                ]);
                return $transaction;
            }

            // Commit the database transaction
            DB::commit();

            // Mark transaction as committed
            $transaction->commit();

            Log::info('Transaction committed successfully', [
                'transaction_id' => $transaction->transaction_id,
                'type' => $transaction->type,
                'duration' => $transaction->getDuration(),
            ]);

            return $transaction;

        } catch (Exception $e) {
            // Rollback database transaction
            DB::rollBack();

            if ($transaction) {
                $transaction->fail($e->getMessage());
            }

            Log::error('Transaction failed', [
                'transaction_id' => $transaction?->transaction_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Execute a nested transaction (savepoint).
     *
     * @param array $transactionData Transaction metadata
     * @param Closure $operation The database operation to execute
     * @param Transaction|null $parentTransaction Parent transaction for nesting
     * @return Transaction
     * @throws Exception
     */
    public function executeNested(array $transactionData, Closure $operation, ?Transaction $parentTransaction = null): Transaction
    {
        $transactionData['parent_transaction_id'] = $parentTransaction?->transaction_id;
        
        return $this->execute($transactionData, $operation);
    }

    /**
     * Retry a failed transaction.
     *
     * @param Transaction $transaction The failed transaction to retry
     * @param Closure $operation The operation to retry
     * @return Transaction
     * @throws Exception
     */
    public function retry(Transaction $transaction, Closure $operation): Transaction
    {
        if (!$transaction->canRetry()) {
            throw new Exception('Transaction cannot be retried: max retry attempts reached');
        }

        $transaction->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        return $this->execute($transaction->toArray(), $operation);
    }

    /**
     * Rollback a committed transaction (compensating transaction).
     *
     * @param Transaction $transaction The transaction to rollback
     * @param Closure|null $compensatingOperation Optional compensating operation
     * @return Transaction
     * @throws Exception
     */
    public function rollbackCommitted(Transaction $transaction, ?Closure $compensatingOperation = null): Transaction
    {
        try {
            DB::beginTransaction();

            // Execute compensating operation if provided
            if ($compensatingOperation) {
                $compensatingOperation($transaction);
            }

            // Create rollback transaction record
            $rollbackTransaction = Transaction::begin([
                'type' => 'rollback',
                'table_name' => $transaction->table_name,
                'record_id' => $transaction->record_id,
                'user_id' => $transaction->user_id,
                'data_before' => $transaction->data_after,
                'data_after' => $transaction->data_before,
                'changed_fields' => $transaction->changed_fields,
                'reason' => "Rollback of transaction {$transaction->transaction_id}",
                'parent_transaction_id' => $transaction->transaction_id,
            ]);

            DB::commit();
            $rollbackTransaction->commit();

            Log::info('Committed transaction rolled back', [
                'original_transaction_id' => $transaction->transaction_id,
                'rollback_transaction_id' => $rollbackTransaction->transaction_id,
            ]);

            return $rollbackTransaction;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to rollback committed transaction', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get transaction statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => Transaction::count(),
            'pending' => Transaction::pending()->count(),
            'committed' => Transaction::where('status', 'committed')->count(),
            'rolled_back' => Transaction::where('status', 'rolled_back')->count(),
            'failed' => Transaction::failed()->count(),
            'requires_review' => Transaction::requiresReview()->count(),
            'avg_duration' => Transaction::whereNotNull('completed_at')
                ->get()
                ->avg(fn($t) => $t->getDuration()),
        ];
    }

    /**
     * Clean up old completed transactions.
     *
     * @param int $daysToKeep Number of days to keep transactions
     * @return int Number of deleted transactions
     */
    public function cleanupOldTransactions(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        $deleted = Transaction::where('status', '!=', 'pending')
            ->where('completed_at', '<', $cutoffDate)
            ->delete();

        Log::info('Cleaned up old transactions', [
            'deleted_count' => $deleted,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);

        return $deleted;
    }
}
