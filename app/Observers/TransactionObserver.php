<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        Log::debug('Transaction created', [
            'transaction_id' => $transaction->transaction_id,
            'type' => $transaction->type,
            'table' => $transaction->table_name,
            'user_id' => $transaction->user_id,
        ]);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        Log::debug('Transaction updated', [
            'transaction_id' => $transaction->transaction_id,
            'status' => $transaction->status,
        ]);
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        Log::debug('Transaction deleted', [
            'transaction_id' => $transaction->transaction_id,
        ]);
    }
}
