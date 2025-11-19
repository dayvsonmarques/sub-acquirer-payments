<?php

namespace App\Http\Controllers;

use App\Jobs\SimulatePixWebhook;
use App\Jobs\SimulateWithdrawWebhook;
use App\Models\PixTransaction;
use App\Models\Subacquirer;
use App\Models\WithdrawTransaction;
use App\Services\SubacquirerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClientAreaController extends Controller
{
    public function __construct(
        protected SubacquirerService $subacquirerService
    ) {
    }

    public function index()
    {
        $user = auth()->user();
        
        $pixTransactions = PixTransaction::where('user_id', $user->id)
            ->with('subacquirer')
            ->latest()
            ->paginate(5, ['*'], 'pixPage');

        $withdrawTransactions = WithdrawTransaction::where('user_id', $user->id)
            ->with('subacquirer')
            ->latest()
            ->paginate(5, ['*'], 'withdrawPage');

        return view('client-area.index', compact('pixTransactions', 'withdrawTransactions'));
    }

    public function createPix()
    {
        $subacquirers = Subacquirer::where('is_active', true)->get();
        return view('client-area.create-pix', compact('subacquirers'));
    }

    public function createWithdraw()
    {
        $subacquirers = Subacquirer::where('is_active', true)->get();
        return view('client-area.create-withdraw', compact('subacquirers'));
    }

    public function storePix(Request $request)
    {
        $request->validate([
            'subacquirer_id' => 'required|exists:subacquirers,id',
            'amount' => 'required|numeric|min:0.01',
            'pix_key' => 'required|string|max:255',
            'pix_key_type' => 'required|string|in:cpf,email,phone,random',
            'description' => 'nullable|string|max:500',
        ]);

        $subacquirer = Subacquirer::findOrFail($request->subacquirer_id);
        return $this->processPix($request, $subacquirer);
    }

    public function storeWithdraw(Request $request)
    {
        $request->validate([
            'subacquirer_id' => 'required|exists:subacquirers,id',
            'amount' => 'required|numeric|min:0.01',
            'bank_code' => 'required|string|max:10',
            'agency' => 'required|string|max:20',
            'account' => 'required|string|max:20',
            'account_type' => 'required|string|in:checking,savings',
            'account_holder_name' => 'required|string|max:255',
            'account_holder_document' => 'required|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

        $subacquirer = Subacquirer::findOrFail($request->subacquirer_id);
        return $this->processWithdraw($request, $subacquirer);
    }

    public function processTransaction(Request $request)
    {
        $request->validate([
            'subacquirer_id' => 'required|exists:subacquirers,id',
            'transaction_type' => 'required|in:pix,withdraw',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $subacquirer = Subacquirer::findOrFail($request->subacquirer_id);

        if ($request->transaction_type === 'pix') {
            return $this->processPix($request, $subacquirer);
        } else {
            return $this->processWithdraw($request, $subacquirer);
        }
    }

    private function processPix(Request $request, Subacquirer $subacquirer)
    {
        try {
            $user = auth()->user();
            $implementation = $this->subacquirerService->getImplementation($subacquirer);
            $transactionId = 'PIX-' . Str::upper(Str::random(16)) . '-' . time();

            $amount = (float) $request->amount;
            
            if ($amount <= 0) {
                return back()->with('error', 'O valor deve ser maior que zero.')->withInput();
            }

            Log::debug('Client Area PIX Request Data', [
                'raw_amount' => $request->amount,
                'amount_type' => gettype($request->amount),
                'converted_amount' => $amount,
                'converted_amount_type' => gettype($amount),
            ]);

            $requestData = [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'pix_key' => $request->pix_key,
                'pix_key_type' => $request->pix_key_type,
                'description' => $request->description ?? null,
            ];

            $transaction = DB::transaction(function () use ($user, $subacquirer, $transactionId, $request, $requestData) {
                return PixTransaction::create([
                    'user_id' => $user->id,
                    'subacquirer_id' => $subacquirer->id,
                    'transaction_id' => $transactionId,
                    'amount' => $request->amount,
                    'pix_key' => $request->pix_key,
                    'pix_key_type' => $request->pix_key_type,
                    'status' => PixTransaction::STATUS_PENDING,
                    'description' => $request->description ?? null,
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
                
                Log::error('Client Area PIX Transaction Failed', [
                    'transaction_id' => $transactionId,
                    'user_id' => $user->id,
                    'subacquirer' => $subacquirer->code,
                    'error' => $response['error'] ?? 'Unknown error',
                ]);

                return back()->with('error', 'Erro ao processar transação PIX: ' . ($response['error'] ?? 'Erro desconhecido'))->withInput();
            }

            SimulatePixWebhook::dispatch($transaction->id)->delay(now()->addSeconds(rand(5, 10)));

            Log::info('Client Area PIX Transaction Created', [
                'transaction_id' => $transactionId,
                'user_id' => $user->id,
                'subacquirer' => $subacquirer->code,
            ]);

            return redirect()->route('client-area.index')
                ->with('success', 'Transação PIX criada com sucesso! O webhook será processado em alguns segundos.');
        } catch (\Exception $e) {
            Log::error('Client Area PIX Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erro ao processar transação: ' . $e->getMessage())->withInput();
        }
    }

    private function processWithdraw(Request $request, Subacquirer $subacquirer)
    {
        try {
            $user = auth()->user();
            $implementation = $this->subacquirerService->getImplementation($subacquirer);
            $transactionId = 'WD-' . Str::upper(Str::random(16)) . '-' . time();

            $requestData = [
                'transaction_id' => $transactionId,
                'amount' => (float) $request->amount,
                'bank_code' => $request->bank_code,
                'agency' => $request->agency,
                'account' => $request->account,
                'account_type' => $request->account_type,
                'account_holder_name' => $request->account_holder_name,
                'account_holder_document' => $request->account_holder_document,
                'description' => $request->description ?? null,
            ];

            $transaction = DB::transaction(function () use ($user, $subacquirer, $transactionId, $request, $requestData) {
                return WithdrawTransaction::create([
                    'user_id' => $user->id,
                    'subacquirer_id' => $subacquirer->id,
                    'transaction_id' => $transactionId,
                    'amount' => $request->amount,
                    'bank_code' => $request->bank_code,
                    'agency' => $request->agency,
                    'account' => $request->account,
                    'account_type' => $request->account_type,
                    'account_holder_name' => $request->account_holder_name,
                    'account_holder_document' => $request->account_holder_document,
                    'status' => WithdrawTransaction::STATUS_PENDING,
                    'description' => $request->description ?? null,
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
                
                Log::error('Client Area Withdraw Transaction Failed', [
                    'transaction_id' => $transactionId,
                    'user_id' => $user->id,
                    'subacquirer' => $subacquirer->code,
                    'error' => $response['error'] ?? 'Unknown error',
                ]);

                return back()->with('error', 'Erro ao processar transação de Saque: ' . ($response['error'] ?? 'Erro desconhecido'))->withInput();
            }

            SimulateWithdrawWebhook::dispatch($transaction->id)->delay(now()->addSeconds(rand(5, 10)));

            Log::info('Client Area Withdraw Transaction Created', [
                'transaction_id' => $transactionId,
                'user_id' => $user->id,
                'subacquirer' => $subacquirer->code,
            ]);

            return redirect()->route('client-area.index')
                ->with('success', 'Transação de Saque criada com sucesso! O webhook será processado em alguns segundos.');
        } catch (\Exception $e) {
            Log::error('Client Area Withdraw Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erro ao processar transação: ' . $e->getMessage())->withInput();
        }
    }
}
