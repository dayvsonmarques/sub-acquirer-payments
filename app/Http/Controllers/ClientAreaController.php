<?php

namespace App\Http\Controllers;

use App\Models\PixTransaction;
use App\Models\Subacquirer;
use App\Models\WithdrawTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClientAreaController extends Controller
{
    public function index()
    {
        $pixTransactions = PixTransaction::with('subacquirer')
            ->latest()
            ->paginate(10, ['*'], 'pix_page');

        $withdrawTransactions = WithdrawTransaction::with('subacquirer')
            ->latest()
            ->paginate(10, ['*'], 'withdraw_page');

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
        $data = [
            'transaction_id' => 'PIX-' . strtoupper(uniqid()) . '-' . time(),
            'amount' => $request->amount,
            'pix_key' => $request->pix_key,
            'pix_key_type' => $request->pix_key_type,
            'description' => $request->description,
        ];

        try {
            $url = rtrim($subacquirer->base_url, '/') . '/pix/create';
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'x-mock-response-name' => '[SUCESSO_PIX] pix_create',
                ])
                ->post($url, $data);

            $responseData = $response->json();

            Log::info("Client Area PIX Request", [
                'subacquirer' => $subacquirer->code,
                'url' => $url,
                'request' => $data,
                'response' => $responseData,
            ]);

            if ($response->successful()) {
                return redirect()->route('client-area.index')
                    ->with('success', 'Transação PIX processada com sucesso!');
            }

            return back()->with('error', 'Erro ao processar transação PIX: ' . ($responseData['message'] ?? 'Erro desconhecido'))->withInput();
        } catch (\Exception $e) {
            Log::error('Client Area PIX Error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erro ao processar transação: ' . $e->getMessage())->withInput();
        }
    }

    private function processWithdraw(Request $request, Subacquirer $subacquirer)
    {
        $data = [
            'transaction_id' => 'WD-' . strtoupper(uniqid()) . '-' . time(),
            'amount' => $request->amount,
            'bank_code' => $request->bank_code,
            'agency' => $request->agency,
            'account' => $request->account,
            'account_type' => $request->account_type,
            'account_holder_name' => $request->account_holder_name,
            'account_holder_document' => $request->account_holder_document,
            'description' => $request->description,
        ];

        try {
            $url = rtrim($subacquirer->base_url, '/') . '/withdraw';
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'x-mock-response-name' => '[SUCESSO_WD] withdraw',
                ])
                ->post($url, $data);

            $responseData = $response->json();

            Log::info("Client Area Withdraw Request", [
                'subacquirer' => $subacquirer->code,
                'url' => $url,
                'request' => $data,
                'response' => $responseData,
            ]);

            if ($response->successful()) {
                return redirect()->route('client-area.index')
                    ->with('success', 'Transação de Saque processada com sucesso!');
            }

            return back()->with('error', 'Erro ao processar transação de Saque: ' . ($responseData['message'] ?? 'Erro desconhecido'))->withInput();
        } catch (\Exception $e) {
            Log::error('Client Area Withdraw Error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erro ao processar transação: ' . $e->getMessage())->withInput();
        }
    }
}
