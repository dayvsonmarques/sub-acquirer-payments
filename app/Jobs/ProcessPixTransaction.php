<?php

namespace App\Jobs;

use App\Jobs\SimulatePixWebhook;
use App\Models\PixTransaction;
use App\Services\SubacquirerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPixTransaction implements ShouldQueue
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
        $this->onQueue('transactions');
    }

    public function handle(SubacquirerService $subacquirerService): void
    {
        $transaction = PixTransaction::with('subacquirer')->find($this->transactionId);

        if (!$transaction) {
            Log::warning('PIX Transaction not found for processing', [
                'transaction_id' => $this->transactionId,
            ]);
            return;
        }

        if ($transaction->status !== PixTransaction::STATUS_PENDING) {
            Log::info('PIX Transaction already processed', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
            ]);
            return;
        }

        try {
            $subacquirer = $transaction->subacquirer;
            $implementation = $subacquirerService->getImplementation($subacquirer);

            $requestData = $transaction->request_data ?? [];

            $response = $implementation->processPix($requestData);

            $transaction->update([
                'response_data' => $response,
                'external_id' => $response['external_id'] ?? null,
            ]);

            if (!$response['success']) {
                $transaction->update(['status' => PixTransaction::STATUS_FAILED]);

                Log::error('PIX Transaction processing failed', [
                    'transaction_id' => $transaction->id,
                    'error' => $response['error'] ?? 'Unknown error',
                ]);
                return;
            }

            $transaction->markAsProcessing();

            $delayMin = config('webhooks.simulation.delay_min', 5);
            $delayMax = config('webhooks.simulation.delay_max', 10);
            $delay = rand($delayMin, $delayMax);
            
            SimulatePixWebhook::dispatch($transaction->id)
                ->delay(now()->addSeconds($delay));

            Log::info('PIX Transaction processed successfully', [
                'transaction_id' => $transaction->id,
                'external_id' => $transaction->external_id,
                'subacquirer' => $subacquirer->code,
            ]);
        } catch (\Exception $e) {
            $transaction->update(['status' => PixTransaction::STATUS_FAILED]);

            Log::error('PIX Transaction processing error', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $transaction = PixTransaction::find($this->transactionId);

        if ($transaction && $transaction->status === PixTransaction::STATUS_PENDING) {
            $transaction->update([
                'status' => PixTransaction::STATUS_FAILED,
            ]);

            Log::error('PIX Transaction processing failed after retries', [
                'transaction_id' => $this->transactionId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
