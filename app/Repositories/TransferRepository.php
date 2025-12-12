<?php

namespace App\Repositories;

use App\Models\Transfer;
use Illuminate\Database\Eloquent\Collection;

class TransferRepository
{
    public function create(array $data): Transfer
    {
        return Transfer::create($data);
    }

    public function findById(int $id): ?Transfer
    {
        return Transfer::with(['senderWallet.user', 'receiverWallet.user'])->find($id);
    }

    public function findByWalletId(int $walletId): Collection
    {
        return Transfer::where('sender_wallet_id', $walletId)
            ->orWhere('receiver_wallet_id', $walletId)
            ->with(['senderWallet.user', 'receiverWallet.user'])
            ->get();
    }

    public function findByReference(string $reference): ?Transfer
    {
        return Transfer::where('reference', $reference)
            ->with(['senderWallet.user', 'receiverWallet.user'])
            ->first();
    }
}

