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
        $transaction = PixTransaction::with('subacquirer')->find($this->transactionId);

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

        $webhookData = $this->generateWebhookPayload($transaction);

        $transaction->update([
            'webhook_data' => $webhookData,
        ]);

        $transaction->markAsConfirmed();

        Log::info('PIX Webhook simulated successfully', [
            'transaction_id' => $transaction->id,
            'external_id' => $transaction->external_id,
            'subacquirer' => $transaction->subacquirer->code,
            'webhook_data' => $webhookData,
        ]);
    }

    private function generateWebhookPayload(PixTransaction $transaction): array
    {
        $subacquirer = $transaction->subacquirer;
        
        if ($subacquirer->code === 'subadqa') {
            return [
                'event' => 'pix_payment_confirmed',
                'transaction_id' => $transaction->external_id ?? $transaction->transaction_id,
                'pix_id' => 'PIX' . strtoupper(substr(uniqid(), -9)),
                'status' => 'CONFIRMED',
                'amount' => (float) $transaction->amount,
                'payer_name' => 'João da Silva',
                'payer_cpf' => '12345678900',
                'payment_date' => now()->toIso8601String(),
                'metadata' => [
                    'source' => 'SubadqA',
                    'environment' => 'sandbox'
                ]
            ];
        } else {
            return [
                'type' => 'pix.status_update',
                'data' => [
                    'id' => $transaction->external_id ?? 'PX' . strtoupper(substr(uniqid(), -9)),
                    'status' => 'PAID',
                    'value' => (float) $transaction->amount,
                    'payer' => [
                        'name' => 'Maria Oliveira',
                        'document' => '98765432100'
                    ],
                    'confirmed_at' => now()->toIso8601String()
                ],
                'signature' => bin2hex(random_bytes(6))
            ];
        }
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
