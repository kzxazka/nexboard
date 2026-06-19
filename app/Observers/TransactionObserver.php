<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\User;
use App\Notifications\SystemNotification;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        if ($user = User::find($transaction->user_id)) {
            $type = ucfirst($transaction->type);
            $amount = '$' . number_format($transaction->amount, 2);
            $color = $transaction->type === 'income' ? 'success' : 'warning';
            
            $user->notify(new SystemNotification(
                "New {$type} Logged",
                "A {$transaction->type} of {$amount} was recorded.",
                $color,
                route('finance.index')
            ));
        }
    }
}
