<?php

namespace App\Services;

use App\Models\PixTransaction;
use App\Models\WithdrawTransaction;
use Illuminate\Support\Facades\Log;

class WebhookProcessor
{
    public function processPixWebhook(PixTransaction $transaction, array $webhookData): bool
    {
        if (!$transaction->isPending() && !$transaction->isProcessing()) {
            Log::info('PIX Transaction already processed', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
            ]);
            return false;
        }

        $transaction->update([
            'webhook_data' => $webhookData,
        ]);

        $transaction->markAsConfirmed();

        Log::info('PIX Webhook processed successfully', [
            'transaction_id' => $transaction->id,
            'external_id' => $transaction->external_id,
            'subacquirer' => $transaction->subacquirer->code,
            'webhook_data' => $webhookData,
        ]);

        return true;
    }

    public function processWithdrawWebhook(WithdrawTransaction $transaction, array $webhookData): bool
    {
        if (!$transaction->isPending() && !$transaction->isProcessing()) {
            Log::info('Withdraw Transaction already processed', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
            ]);
            return false;
        }

        $transaction->update([
            'webhook_data' => $webhookData,
        ]);

        $transaction->markAsPaid();

        Log::info('Withdraw Webhook processed successfully', [
            'transaction_id' => $transaction->id,
            'external_id' => $transaction->external_id,
            'subacquirer' => $transaction->subacquirer->code,
            'webhook_data' => $webhookData,
        ]);

        return true;
    }

    public function generatePixWebhookPayload(PixTransaction $transaction): array
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
                'payment_date' => now()->format('c'),
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
                    'confirmed_at' => now()->format('c')
                ],
                'signature' => bin2hex(random_bytes(6))
            ];
        }
    }

    public function generateWithdrawWebhookPayload(WithdrawTransaction $transaction): array
    {
        $subacquirer = $transaction->subacquirer;
        
        if ($subacquirer->code === 'subadqa') {
            return [
                'event' => 'withdraw_completed',
                'withdraw_id' => $transaction->external_id ?? 'WD' . strtoupper(substr(uniqid(), -9)),
                'transaction_id' => $transaction->transaction_id,
                'status' => 'SUCCESS',
                'amount' => (float) $transaction->amount,
                'requested_at' => $transaction->created_at->format('c'),
                'completed_at' => now()->format('c'),
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
                    'processed_at' => now()->format('c')
                ],
                'signature' => bin2hex(random_bytes(6))
            ];
        }
    }
}

