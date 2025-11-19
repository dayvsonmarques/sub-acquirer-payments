<?php

namespace App\Events;

use App\Models\WithdrawTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionPaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WithdrawTransaction $transaction
    ) {
    }
}

