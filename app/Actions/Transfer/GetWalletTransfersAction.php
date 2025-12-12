<?php

namespace App\Actions\Transfer;

use App\Actions\Action;
use App\Repositories\TransferRepository;

class GetWalletTransfersAction extends Action
{
    public function __construct(
        private readonly TransferRepository $transferRepository
    ) {}

    /**
     * Execute getting wallet transfers.
     *
     * @param int $walletId
     * @return array
     */
    public function execute(...$arguments): array
    {
        if (empty($arguments) || !is_int($arguments[0])) {
            throw new \InvalidArgumentException('Wallet ID must be provided as an integer');
        }
        
        $walletId = (int) $arguments[0];
        
        $transfers = $this->transferRepository->findByWalletId($walletId);

        return $transfers->map(function ($transfer) use ($walletId) {
            return [
                'id' => $transfer->id,
                'reference' => $transfer->reference,
                'type' => $transfer->sender_wallet_id === $walletId ? 'outgoing' : 'incoming',
                'amount' => round((float) $transfer->amount, 2),
                'sender' => $transfer->senderWallet->user->name,
                'receiver' => $transfer->receiverWallet->user->name,
                'description' => $transfer->description,
                'status' => $transfer->status,
                'created_at' => $transfer->created_at,
            ];
        })->toArray();
    }
}

