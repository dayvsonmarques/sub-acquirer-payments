<?php

namespace App\Http\Controllers\Api;

use App\Events\TransactionConfirmed;
use App\Events\TransactionPaid;
use App\Http\Controllers\Controller;
use App\Models\PixTransaction;
use App\Models\Subacquirer;
use App\Models\WebhookAttempt;
use App\Models\WithdrawTransaction;
use App\Services\WebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class WebhookController extends Controller
{
    public function __construct(
        protected WebhookProcessor $webhookProcessor
    ) {
    }

    #[OA\Post(
        path: "/webhooks/pix/{subacquirer}",
        summary: "Receive PIX webhook from subacquirer",
        description: "Receives and processes webhook notifications from subacquirers for PIX transactions. Supports both SubadqA and SubadqB webhook formats.",
        tags: ["Webhooks"],
        parameters: [
            new OA\Parameter(
                name: "subacquirer",
                in: "path",
                required: true,
                description: "Subacquirer code (subadqa or subadqb)",
                schema: new OA\Schema(type: "string", enum: ["subadqa", "subadqb"])
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent()
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Webhook processed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Webhook processed successfully"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad request - invalid webhook data or transaction not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Transaction not found"),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "The given data was invalid."),
                        new OA\Property(property: "errors", type: "object"),
                    ]
                )
            ),
        ]
    )]
    public function pix(Request $request, string $subacquirer): JsonResponse
    {
        try {
            $subacquirerModel = Subacquirer::where('code', strtolower($subacquirer))->first();

            if (!$subacquirerModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subacquirer not found',
                ], 404);
            }

            $webhookData = $request->all();
            $transactionId = $this->extractTransactionId($webhookData, $subacquirerModel->code, 'pix');

            if (!$transactionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction ID not found in webhook data',
                ], 400);
            }

            $transaction = PixTransaction::where('external_id', $transactionId)
                ->orWhere('transaction_id', $transactionId)
                ->where('subacquirer_id', $subacquirerModel->id)
                ->first();

            if (!$transaction) {
                WebhookAttempt::create([
                    'transaction_type' => 'pix',
                    'transaction_id' => 0,
                    'status' => WebhookAttempt::STATUS_FAILED,
                    'payload' => $webhookData,
                    'source' => WebhookAttempt::SOURCE_EXTERNAL,
                    'error_message' => 'Transaction not found',
                    'attempt_number' => 1,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 400);
            }

            $attempt = WebhookAttempt::create([
                'transaction_type' => 'pix',
                'transaction_id' => $transaction->id,
                'status' => WebhookAttempt::STATUS_PENDING,
                'payload' => $webhookData,
                'source' => WebhookAttempt::SOURCE_EXTERNAL,
                'attempt_number' => WebhookAttempt::where('transaction_type', 'pix')
                    ->where('transaction_id', $transaction->id)
                    ->count() + 1,
            ]);

            $processed = $this->webhookProcessor->processPixWebhook($transaction, $webhookData);

            if ($processed) {
                $attempt->update([
                    'status' => WebhookAttempt::STATUS_SUCCESS,
                    'response' => ['processed' => true],
                ]);

                event(new TransactionConfirmed($transaction));

                return response()->json([
                    'success' => true,
                    'message' => 'Webhook processed successfully',
                ], 200);
            }

            $attempt->update([
                'status' => WebhookAttempt::STATUS_FAILED,
                'error_message' => 'Transaction already processed',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Transaction already processed',
            ], 400);
        } catch (\Exception $e) {
            Log::error('PIX Webhook processing error', [
                'subacquirer' => $subacquirer,
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

    #[OA\Post(
        path: "/webhooks/withdraw/{subacquirer}",
        summary: "Receive withdraw webhook from subacquirer",
        description: "Receives and processes webhook notifications from subacquirers for withdraw transactions. Supports both SubadqA and SubadqB webhook formats.",
        tags: ["Webhooks"],
        parameters: [
            new OA\Parameter(
                name: "subacquirer",
                in: "path",
                required: true,
                description: "Subacquirer code (subadqa or subadqb)",
                schema: new OA\Schema(type: "string", enum: ["subadqa", "subadqb"])
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent()
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Webhook processed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Webhook processed successfully"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad request - invalid webhook data or transaction not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Transaction not found"),
                    ]
                )
            ),
        ]
    )]
    public function withdraw(Request $request, string $subacquirer): JsonResponse
    {
        try {
            $subacquirerModel = Subacquirer::where('code', strtolower($subacquirer))->first();

            if (!$subacquirerModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subacquirer not found',
                ], 404);
            }

            $webhookData = $request->all();
            $transactionId = $this->extractTransactionId($webhookData, $subacquirerModel->code, 'withdraw');

            if (!$transactionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction ID not found in webhook data',
                ], 400);
            }

            $transaction = WithdrawTransaction::where('external_id', $transactionId)
                ->orWhere('transaction_id', $transactionId)
                ->where('subacquirer_id', $subacquirerModel->id)
                ->first();

            if (!$transaction) {
                WebhookAttempt::create([
                    'transaction_type' => 'withdraw',
                    'transaction_id' => 0,
                    'status' => WebhookAttempt::STATUS_FAILED,
                    'payload' => $webhookData,
                    'source' => WebhookAttempt::SOURCE_EXTERNAL,
                    'error_message' => 'Transaction not found',
                    'attempt_number' => 1,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 400);
            }

            $attempt = WebhookAttempt::create([
                'transaction_type' => 'withdraw',
                'transaction_id' => $transaction->id,
                'status' => WebhookAttempt::STATUS_PENDING,
                'payload' => $webhookData,
                'source' => WebhookAttempt::SOURCE_EXTERNAL,
                'attempt_number' => WebhookAttempt::where('transaction_type', 'withdraw')
                    ->where('transaction_id', $transaction->id)
                    ->count() + 1,
            ]);

            $processed = $this->webhookProcessor->processWithdrawWebhook($transaction, $webhookData);

            if ($processed) {
                $attempt->update([
                    'status' => WebhookAttempt::STATUS_SUCCESS,
                    'response' => ['processed' => true],
                ]);

                event(new TransactionPaid($transaction));

                return response()->json([
                    'success' => true,
                    'message' => 'Webhook processed successfully',
                ], 200);
            }

            $attempt->update([
                'status' => WebhookAttempt::STATUS_FAILED,
                'error_message' => 'Transaction already processed',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Transaction already processed',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Withdraw Webhook processing error', [
                'subacquirer' => $subacquirer,
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

    private function extractTransactionId(array $webhookData, string $subacquirerCode, string $type): ?string
    {
        $code = strtolower($subacquirerCode);

        if ($type === 'pix') {
            if ($code === 'subadqa') {
                return $webhookData['transaction_id'] ?? $webhookData['pix_id'] ?? null;
            } else {
                return $webhookData['data']['id'] ?? null;
            }
        } else {
            if ($code === 'subadqa') {
                return $webhookData['transaction_id'] ?? $webhookData['withdraw_id'] ?? null;
            } else {
                return $webhookData['data']['id'] ?? null;
            }
        }
    }
}

