<?php

namespace App\Jobs;

use App\Events\TransactionPaid;
use App\Models\WebhookAttempt;
use App\Models\WithdrawTransaction;
use App\Services\WebhookProcessor;
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
        return config('webhooks.retry.backoff', [5, 10, 30]);
    }

    public function __construct(
        public int $transactionId
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(WebhookProcessor $webhookProcessor): void
    {
        $lockKey = "withdraw_webhook_lock_{$this->transactionId}";
        $lockTimeout = config('webhooks.lock.timeout', 30);
        
        $lock = Cache::lock($lockKey, $lockTimeout);
        
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

            if (!$transaction->isPending() && !$transaction->isProcessing()) {
                Log::info('Withdraw Transaction already processed', [
                    'transaction_id' => $transaction->id,
                    'status' => $transaction->status,
                ]);
                return;
            }

            $webhookData = $webhookProcessor->generateWithdrawWebhookPayload($transaction);

            $attempt = WebhookAttempt::create([
                'transaction_type' => 'withdraw',
                'transaction_id' => $transaction->id,
                'status' => WebhookAttempt::STATUS_PENDING,
                'payload' => $webhookData,
                'source' => WebhookAttempt::SOURCE_SIMULATION,
                'attempt_number' => WebhookAttempt::where('transaction_type', 'withdraw')
                    ->where('transaction_id', $transaction->id)
                    ->count() + 1,
            ]);

            $processed = $webhookProcessor->processWithdrawWebhook($transaction, $webhookData);

            if ($processed) {
                $attempt->update([
                    'status' => WebhookAttempt::STATUS_SUCCESS,
                    'response' => ['processed' => true],
                ]);

                event(new TransactionPaid($transaction));

                Log::info('Withdraw Webhook simulated successfully', [
                    'transaction_id' => $transaction->id,
                    'external_id' => $transaction->external_id,
                    'subacquirer' => $transaction->subacquirer->code,
                    'webhook_data' => $webhookData,
                ]);
            } else {
                $attempt->update([
                    'status' => WebhookAttempt::STATUS_FAILED,
                    'error_message' => 'Transaction already processed',
                ]);
            }
        } finally {
            $lock->release();
        }
    }

    public function failed(\Throwable $exception): void
    {
        $transaction = WithdrawTransaction::find($this->transactionId);

        if ($transaction && ($transaction->isPending() || $transaction->isProcessing())) {
            $transaction->update([
                'status' => WithdrawTransaction::STATUS_FAILED,
            ]);

            WebhookAttempt::create([
                'transaction_type' => 'withdraw',
                'transaction_id' => $this->transactionId,
                'status' => WebhookAttempt::STATUS_FAILED,
                'source' => WebhookAttempt::SOURCE_SIMULATION,
                'error_message' => $exception->getMessage(),
                'attempt_number' => WebhookAttempt::where('transaction_type', 'withdraw')
                    ->where('transaction_id', $this->transactionId)
                    ->count() + 1,
            ]);

            Log::error('Withdraw Webhook simulation failed', [
                'transaction_id' => $this->transactionId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
