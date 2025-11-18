<?php

namespace App\Jobs;

use App\Models\PixTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SimulatePixWebhook implements ShouldQueue
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
        $transaction = PixTransaction::find($this->transactionId);

        if (!$transaction) {
            Log::warning('PIX Transaction not found for webhook simulation', [
                'transaction_id' => $this->transactionId,
            ]);
            return;
        }

        if (!$transaction->isPending()) {
            Log::info('PIX Transaction already processed', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
            ]);
            return;
        }

        $delay = rand(5, 10);
        sleep($delay);

        $webhookData = [
            'transaction_id' => $transaction->external_id ?? $transaction->transaction_id,
            'status' => PixTransaction::STATUS_CONFIRMED,
            'confirmed_at' => now()->toIso8601String(),
            'simulated' => true,
        ];

        $transaction->update([
            'webhook_data' => $webhookData,
        ]);

        $transaction->markAsConfirmed();

        Log::info('PIX Webhook simulated successfully', [
            'transaction_id' => $transaction->id,
            'external_id' => $transaction->external_id,
            'webhook_data' => $webhookData,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $transaction = PixTransaction::find($this->transactionId);

        if ($transaction && $transaction->isPending()) {
            $transaction->update([
                'status' => PixTransaction::STATUS_FAILED,
            ]);

            Log::error('PIX Webhook simulation failed', [
                'transaction_id' => $this->transactionId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
