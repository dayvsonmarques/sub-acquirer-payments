<?php

namespace App\Http\Controllers;

use App\Models\PixTransaction;
use App\Models\WithdrawTransaction;
use Illuminate\Support\Facades\Log;

class ClientAreaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        $isAdmin = is_null($user->subacquirer_id);
        
        if ($isAdmin) {
            $pixTransactions = PixTransaction::with('subacquirer')
                ->latest()
                ->paginate(10, ['*'], 'pixPage');
            
            $withdrawTransactions = WithdrawTransaction::with('subacquirer')
                ->latest()
                ->paginate(10, ['*'], 'withdrawPage');
        } else {
            $pixTransactions = PixTransaction::where('user_id', $user->id)
                ->with('subacquirer')
                ->latest()
                ->paginate(10, ['*'], 'pixPage');
            
            $withdrawTransactions = WithdrawTransaction::where('user_id', $user->id)
                ->with('subacquirer')
                ->latest()
                ->paginate(10, ['*'], 'withdrawPage');
        }

        Log::debug('Client Area - User transactions', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'is_admin' => $isAdmin,
            'pix_count' => $pixTransactions->total(),
            'withdraw_count' => $withdrawTransactions->total(),
        ]);

        return view('client-area.index', compact('pixTransactions', 'withdrawTransactions'));
    }

    public function showPix(PixTransaction $pixTransaction)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $isAdmin = is_null($user->subacquirer_id);
        
        if (!$isAdmin && $pixTransaction->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this transaction');
        }

        $pixTransaction->load(['user', 'subacquirer']);

        return view('client-area.show-pix', compact('pixTransaction'));
    }

    public function showWithdraw(WithdrawTransaction $withdrawTransaction)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $isAdmin = is_null($user->subacquirer_id);
        
        if (!$isAdmin && $withdrawTransaction->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this transaction');
        }

        $withdrawTransaction->load(['user', 'subacquirer']);

        return view('client-area.show-withdraw', compact('withdrawTransaction'));
    }
}
