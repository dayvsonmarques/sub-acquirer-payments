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
use OpenApi\Attributes as OA;

class PixController extends Controller
{
    public function __construct(
        protected SubacquirerService $subacquirerService
    ) {
    }

    #[OA\Post(
        path: "/pix",
        summary: "Create a PIX transaction",
        description: "Creates a new PIX transaction and sends it to the user's configured subacquirer. A webhook will be simulated after 5-10 seconds to update the transaction status.",
        tags: ["PIX Transactions"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount", "pix_key", "pix_key_type"],
                properties: [
                    new OA\Property(property: "amount", type: "number", format: "float", example: 100.50, description: "Transaction amount (minimum 0.01)"),
                    new OA\Property(property: "pix_key", type: "string", example: "12345678900", description: "PIX key (CPF, email, phone, or random key)"),
                    new OA\Property(property: "pix_key_type", type: "string", enum: ["cpf", "email", "phone", "random"], example: "cpf", description: "Type of PIX key"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Payment for services", description: "Optional transaction description"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "PIX transaction created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "PIX transaction created successfully"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "transaction_id", type: "string", example: "PIX-ABCD1234EFGH5678-1699999999"),
                                new OA\Property(property: "external_id", type: "string", nullable: true, example: "ext-123456"),
                                new OA\Property(property: "status", type: "string", example: "PENDING"),
                                new OA\Property(property: "amount", type: "string", example: "100.50"),
                                new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2025-11-17T21:00:00.000000Z"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad request - validation error or user/subacquirer issue",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "User does not have a subacquirer assigned"),
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
            new OA\Response(
                response: 500,
                description: "Internal server error or subacquirer API error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Failed to process PIX transaction"),
                        new OA\Property(property: "error", type: "string", example: "SubadqA API error: Connection timeout"),
                        new OA\Property(property: "transaction_id", type: "string", example: "PIX-ABCD1234EFGH5678-1699999999"),
                    ]
                )
            ),
        ]
    )]
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
                'amount' => (float) $validated['amount'],
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

            SimulatePixWebhook::dispatch($transaction->id)
                ->delay(now()->addSeconds(rand(5, 10)));

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
