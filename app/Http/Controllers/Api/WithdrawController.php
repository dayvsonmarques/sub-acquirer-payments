<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWithdrawRequest;
use App\Jobs\SimulateWithdrawWebhook;
use App\Models\WithdrawTransaction;
use App\Services\SubacquirerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class WithdrawController extends Controller
{
    public function __construct(
        protected SubacquirerService $subacquirerService
    ) {
    }

    #[OA\Post(
        path: "/api/withdraw",
        summary: "Create a withdraw transaction",
        description: "Creates a new withdraw transaction and sends it to the user's configured subacquirer. A webhook will be simulated after 5-10 seconds to update the transaction status.",
        tags: ["Withdraw Transactions"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount", "bank_code", "agency", "account", "account_type", "account_holder_name", "account_holder_document"],
                properties: [
                    new OA\Property(property: "amount", type: "number", format: "float", example: 500.00, description: "Withdraw amount (minimum 0.01)"),
                    new OA\Property(property: "bank_code", type: "string", example: "001", description: "Bank code"),
                    new OA\Property(property: "agency", type: "string", example: "1234", description: "Bank agency number"),
                    new OA\Property(property: "account", type: "string", example: "56789-0", description: "Bank account number"),
                    new OA\Property(property: "account_type", type: "string", enum: ["checking", "savings"], example: "checking", description: "Account type"),
                    new OA\Property(property: "account_holder_name", type: "string", example: "João da Silva", description: "Account holder full name"),
                    new OA\Property(property: "account_holder_document", type: "string", example: "12345678900", description: "Account holder CPF/CNPJ"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Withdraw to bank account", description: "Optional transaction description"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Withdraw transaction created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Withdraw transaction created successfully"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "transaction_id", type: "string", example: "WD-ABCD1234EFGH5678-1699999999"),
                                new OA\Property(property: "external_id", type: "string", nullable: true, example: "ext-456789"),
                                new OA\Property(property: "status", type: "string", example: "PENDING"),
                                new OA\Property(property: "amount", type: "string", example: "500.00"),
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
                        new OA\Property(property: "message", type: "string", example: "Failed to process withdraw transaction"),
                        new OA\Property(property: "error", type: "string", example: "SubadqA API error: Connection timeout"),
                        new OA\Property(property: "transaction_id", type: "string", example: "WD-ABCD1234EFGH5678-1699999999"),
                    ]
                )
            ),
        ]
    )]
    public function store(StoreWithdrawRequest $request): JsonResponse
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
            $transactionId = 'WD-' . Str::upper(Str::random(16)) . '-' . time();

            $requestData = [
                'transaction_id' => $transactionId,
                'amount' => (float) $validated['amount'],
                'bank_code' => $validated['bank_code'],
                'agency' => $validated['agency'],
                'account' => $validated['account'],
                'account_type' => $validated['account_type'],
                'account_holder_name' => $validated['account_holder_name'],
                'account_holder_document' => $validated['account_holder_document'],
                'description' => $validated['description'] ?? null,
            ];

            $transaction = DB::transaction(function () use ($user, $subacquirer, $transactionId, $validated, $requestData) {
                return WithdrawTransaction::create([
                    'user_id' => $user->id,
                    'subacquirer_id' => $subacquirer->id,
                    'transaction_id' => $transactionId,
                    'amount' => $validated['amount'],
                    'bank_code' => $validated['bank_code'],
                    'agency' => $validated['agency'],
                    'account' => $validated['account'],
                    'account_type' => $validated['account_type'],
                    'account_holder_name' => $validated['account_holder_name'],
                    'account_holder_document' => $validated['account_holder_document'],
                    'status' => WithdrawTransaction::STATUS_PENDING,
                    'description' => $validated['description'] ?? null,
                    'request_data' => $requestData,
                ]);
            });

            $response = $implementation->processWithdraw($requestData);

            $transaction->update([
                'response_data' => $response,
                'external_id' => $response['external_id'] ?? null,
            ]);

            if (!$response['success']) {
                $transaction->update(['status' => WithdrawTransaction::STATUS_FAILED]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process withdraw transaction',
                    'error' => $response['error'] ?? 'Unknown error',
                    'transaction_id' => $transactionId,
                ], 500);
            }

            SimulateWithdrawWebhook::dispatch($transaction->id)
                ->delay(now()->addSeconds(rand(5, 10)));

            Log::info('Withdraw Transaction created', [
                'transaction_id' => $transactionId,
                'user_id' => $user->id,
                'subacquirer' => $subacquirer->code,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdraw transaction created successfully',
                'data' => [
                    'transaction_id' => $transactionId,
                    'external_id' => $transaction->external_id,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'created_at' => $transaction->created_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Withdraw Transaction Error', [
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
