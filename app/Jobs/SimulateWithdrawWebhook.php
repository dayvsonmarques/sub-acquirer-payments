<?php

namespace App\Jobs;

use App\Models\WithdrawTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SimulateWithdrawWebhook implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $maxExceptions = 3;
    
    public function backoff(): array
    {
        return [5, 10, 30];
    }

    public function __construct(
        public int $transactionId
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $lockKey = "withdraw_webhook_lock_{$this->transactionId}";
        
        $lock = Cache::lock($lockKey, 30);
        
        if (!$lock->get()) {
            Log::warning('Withdraw Webhook already being processed', [
                'transaction_id' => $this->transactionId,
            ]);
            return;
        }

        try {
            $transaction = WithdrawTransaction::with('subacquirer')->find($this->transactionId);

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

            $webhookData = $this->generateWebhookPayload($transaction);

            $transaction->update([
                'webhook_data' => $webhookData,
            ]);

            $transaction->markAsPaid();

            Log::info('Withdraw Webhook simulated successfully', [
                'transaction_id' => $transaction->id,
                'external_id' => $transaction->external_id,
                'subacquirer' => $transaction->subacquirer->code,
                'webhook_data' => $webhookData,
            ]);
        } finally {
            $lock->release();
        }
    }

    private function generateWebhookPayload(WithdrawTransaction $transaction): array
    {
        $subacquirer = $transaction->subacquirer;
        
        if ($subacquirer->code === 'subadqa') {
            return [
                'event' => 'withdraw_completed',
                'withdraw_id' => $transaction->external_id ?? 'WD' . strtoupper(substr(uniqid(), -9)),
                'transaction_id' => $transaction->transaction_id,
                'status' => 'SUCCESS',
                'amount' => (float) $transaction->amount,
                'requested_at' => $transaction->created_at->toIso8601String(),
                'completed_at' => now()->toIso8601String(),
                'metadata' => [
                    'source' => 'SubadqA',
                    'destination_bank' => 'Itaú'
                ]
            ];
        } else {
            return [
                'type' => 'withdraw.status_update',
                'data' => [
                    'id' => $transaction->external_id ?? 'WDX' . strtoupper(substr(uniqid(), -5)),
                    'status' => 'DONE',
                    'amount' => (float) $transaction->amount,
                    'bank_account' => [
                        'bank' => 'Nubank',
                        'agency' => $transaction->agency,
                        'account' => $transaction->account
                    ],
                    'processed_at' => now()->toIso8601String()
                ],
                'signature' => bin2hex(random_bytes(6))
            ];
        }
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
