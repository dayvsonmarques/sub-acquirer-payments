<?php

namespace App\Jobs;

use App\Models\WithdrawTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SimulateWithdrawWebhook implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [5, 10, 30];

    public function __construct(
        public int $transactionId
    ) {
    }

    public function handle(): void
    {
        $transaction = WithdrawTransaction::find($this->transactionId);

        if (!$transaction) {
            Log::warning('Withdraw Transaction not found for webhook simulation', [
                'transaction_id' => $this->transactionId,
            ]);
            return;
        }

        if (!$transaction->isPending()) {
            Log::info('Withdraw Transaction already processed', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
            ]);
            return;
        }

        $delay = rand(5, 10);
        sleep($delay);

        $webhookData = [
            'transaction_id' => $transaction->external_id ?? $transaction->transaction_id,
            'status' => WithdrawTransaction::STATUS_PAID,
            'paid_at' => now()->toIso8601String(),
            'simulated' => true,
        ];

        $transaction->update([
            'webhook_data' => $webhookData,
        ]);

        $transaction->markAsPaid();

        Log::info('Withdraw Webhook simulated successfully', [
            'transaction_id' => $transaction->id,
            'external_id' => $transaction->external_id,
            'webhook_data' => $webhookData,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $transaction = WithdrawTransaction::find($this->transactionId);

        if ($transaction && $transaction->isPending()) {
            $transaction->update([
                'status' => WithdrawTransaction::STATUS_FAILED,
            ]);

            Log::error('Withdraw Webhook simulation failed', [
                'transaction_id' => $this->transactionId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
