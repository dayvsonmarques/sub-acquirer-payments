<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePixRequest;
use App\Jobs\SimulatePixWebhook;
use App\Models\PixTransaction;
use App\Services\SubacquirerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PixController extends Controller
{
    public function __construct(
        protected SubacquirerService $subacquirerService
    ) {
    }

    public function store(StorePixRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->subacquirer_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have a subacquirer assigned',
                ], 400);
            }

            $subacquirer = $user->subacquirer;

            if (!$subacquirer || !$subacquirer->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subacquirer is not active',
                ], 400);
            }

            $implementation = $this->subacquirerService->getImplementation($subacquirer);
            $validated = $request->validated();
            $transactionId = 'PIX-' . Str::upper(Str::random(16)) . '-' . time();

            $requestData = [
                'transaction_id' => $transactionId,
                'amount' => $validated['amount'],
                'pix_key' => $validated['pix_key'],
                'pix_key_type' => $validated['pix_key_type'],
                'description' => $validated['description'] ?? null,
            ];

            $transaction = DB::transaction(function () use ($user, $subacquirer, $transactionId, $validated, $requestData) {
                return PixTransaction::create([
                    'user_id' => $user->id,
                    'subacquirer_id' => $subacquirer->id,
                    'transaction_id' => $transactionId,
                    'amount' => $validated['amount'],
                    'pix_key' => $validated['pix_key'],
                    'pix_key_type' => $validated['pix_key_type'],
                    'status' => PixTransaction::STATUS_PENDING,
                    'description' => $validated['description'] ?? null,
                    'request_data' => $requestData,
                ]);
            });

            $response = $implementation->processPix($requestData);

            $transaction->update([
                'response_data' => $response,
                'external_id' => $response['external_id'] ?? null,
            ]);

            if (!$response['success']) {
                $transaction->update(['status' => PixTransaction::STATUS_FAILED]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process PIX transaction',
                    'error' => $response['error'] ?? 'Unknown error',
                    'transaction_id' => $transactionId,
                ], 500);
            }

            SimulatePixWebhook::dispatch($transaction->id);

            Log::info('PIX Transaction created', [
                'transaction_id' => $transactionId,
                'user_id' => $user->id,
                'subacquirer' => $subacquirer->code,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PIX transaction created successfully',
                'data' => [
                    'transaction_id' => $transactionId,
                    'external_id' => $transaction->external_id,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'created_at' => $transaction->created_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('PIX Transaction Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }
}
