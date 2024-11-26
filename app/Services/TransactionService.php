<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{

    public function increaseBalance(User $user, $amount)
    {
        return DB::transaction(function () use ($user, $amount) {
            $user->balance += $amount;
            $user->save();

            return $user->balance;
        });
    }

    public function decreaseBalance(User $user, $amount)
    {
        return DB::transaction(function () use ($user, $amount) {
            if ($user->balance < $amount) {
                throw new Exception("Insufficient balance to purchase this item.");
            }
        });
    }
}
