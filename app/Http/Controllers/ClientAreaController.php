<?php

namespace App\Http\Controllers;

use App\Models\Subacquirer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClientAreaController extends Controller
{
    public function index()
    {
        $subacquirers = Subacquirer::where('is_active', true)->get();
        return view('client-area.index', compact('subacquirers'));
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
        $request->validate([
            'pix_key' => 'required|string|max:255',
            'pix_key_type' => 'required|string|in:cpf,email,phone,random',
            'description' => 'nullable|string|max:500',
        ]);

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
                return back()->with('success', 'Transação PIX processada com sucesso!')
                    ->with('transaction_data', [
                        'type' => 'PIX',
                        'transaction_id' => $data['transaction_id'],
                        'amount' => $data['amount'],
                        'status' => 'PENDING',
                        'response' => $responseData,
                    ]);
            }

            return back()->with('error', 'Erro ao processar transação PIX: ' . ($responseData['message'] ?? 'Erro desconhecido'));
        } catch (\Exception $e) {
            Log::error('Client Area PIX Error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erro ao processar transação: ' . $e->getMessage());
        }
    }

    private function processWithdraw(Request $request, Subacquirer $subacquirer)
    {
        $request->validate([
            'bank_code' => 'required|string|max:10',
            'agency' => 'required|string|max:20',
            'account' => 'required|string|max:20',
            'account_type' => 'required|string|in:checking,savings',
            'account_holder_name' => 'required|string|max:255',
            'account_holder_document' => 'required|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

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
                return back()->with('success', 'Transação de Saque processada com sucesso!')
                    ->with('transaction_data', [
                        'type' => 'SAQUE',
                        'transaction_id' => $data['transaction_id'],
                        'amount' => $data['amount'],
                        'status' => 'PENDING',
                        'response' => $responseData,
                    ]);
            }

            return back()->with('error', 'Erro ao processar transação de Saque: ' . ($responseData['message'] ?? 'Erro desconhecido'));
        } catch (\Exception $e) {
            Log::error('Client Area Withdraw Error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erro ao processar transação: ' . $e->getMessage());
        }
    }
}
