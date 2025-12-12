<?php

namespace App\Repositories;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;

class WalletRepository
{
    public function create(array $data): Wallet
    {
        return Wallet::create($data);
    }

    public function findByUserId(int $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->first();
    }

    public function findById(int $id): ?Wallet
    {
        return Wallet::find($id);
    }

    public function findByIdWithLock(int $id): ?Wallet
    {
        return Wallet::lockForUpdate()->find($id);
    }

    public function updateBalance(Wallet $wallet, float $amount): bool
    {
        $amount = round($amount, 2);
        
        if ($amount < 0 || $amount > 999999999999.99) {
            throw new \InvalidArgumentException("Balance must be between 0 and 999999999999.99");
        }
        
        return $wallet->update(['balance' => $amount]);
    }

    public function delete(Wallet $wallet): bool
    {
        return $wallet->delete();
    }
}

