<?php

namespace App\Traits;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait LogsTransactions
{
    /**
     * Boot the trait.
     */
    protected static function bootLogsTransactions(): void
    {
        static::created(function ($model) {
            static::logTransaction($model, 'create');
        });

        static::updated(function ($model) {
            static::logTransaction($model, 'update');
        });

        static::deleted(function ($model) {
            static::logTransaction($model, 'delete');
        });
    }

    /**
     * Log a transaction for the model.
     *
     * @param mixed $model The model instance
     * @param string $type The transaction type (create, update, delete)
     * @return Transaction|null
     */
    protected static function logTransaction($model, string $type): ?Transaction
    {
        if (!config('security.transactions.enabled', true)) {
            return null;
        }

        try {
            $tableName = $model->getTable();
            $recordId = $model->getKey();
            $userId = Auth::id();

            $dataBefore = null;
            $dataAfter = null;
            $changedFields = null;

            if ($type === 'update') {
                $dataBefore = $model->getOriginal();
                $dataAfter = $model->getAttributes();
                $changedFields = Transaction::getChangedFields($dataBefore, $dataAfter);
            } elseif ($type === 'create') {
                $dataAfter = $model->getAttributes();
            } elseif ($type === 'delete') {
                $dataBefore = $model->getOriginal();
            }

            // Filter out sensitive fields if needed
            $sensitiveFields = config('security.audit.sensitive_fields', []);
            if (!empty($sensitiveFields)) {
                $dataBefore = static::filterSensitiveFields($dataBefore, $sensitiveFields);
                $dataAfter = static::filterSensitiveFields($dataAfter, $sensitiveFields);
            }

            $transaction = Transaction::create([
                'transaction_id' => \Illuminate\Support\Str::uuid(),
                'type' => $type,
                'status' => 'committed',
                'table_name' => $tableName,
                'record_id' => $recordId,
                'user_id' => $userId,
                'data_before' => $dataBefore,
                'data_after' => $dataAfter,
                'changed_fields' => $changedFields,
                'reason' => static::getTransactionReason($model, $type),
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            Log::debug('Transaction logged', [
                'transaction_id' => $transaction->transaction_id,
                'type' => $type,
                'table' => $tableName,
                'record_id' => $recordId,
            ]);

            return $transaction;

        } catch (\Exception $e) {
            Log::error('Failed to log transaction', [
                'error' => $e->getMessage(),
                'model' => get_class($model),
                'type' => $type,
            ]);
            return null;
        }
    }

    /**
     * Filter sensitive fields from data.
     *
     * @param array|null $data The data to filter
     * @param array $sensitiveFields List of sensitive field names
     * @return array|null
     */
    protected static function filterSensitiveFields(?array $data, array $sensitiveFields): ?array
    {
        if ($data === null) {
            return null;
        }

        foreach ($sensitiveFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Get a human-readable reason for the transaction.
     *
     * @param mixed $model The model instance
     * @param string $type The transaction type
     * @return string
     */
    protected static function getTransactionReason($model, string $type): string
    {
        $modelName = class_basename($model);
        $recordId = $model->getKey();

        return match($type) {
            'create' => "Created new {$modelName} (ID: {$recordId})",
            'update' => "Updated {$modelName} (ID: {$recordId})",
            'delete' => "Deleted {$modelName} (ID: {$recordId})",
            default => "Transaction on {$modelName} (ID: {$recordId})",
        };
    }

    /**
     * Get transaction history for this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTransactionHistory()
    {
        return Transaction::forTable($this->getTable())
            ->where('record_id', $this->getKey())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the last transaction for this model.
     *
     * @return Transaction|null
     */
    public function getLastTransaction(): ?Transaction
    {
        return Transaction::forTable($this->getTable())
            ->where('record_id', $this->getKey())
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
