<?php

namespace App\Actions\Wallet;

use App\Actions\Action;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;

class GetWalletDetailsAction extends Action
{
    public function __construct(
        private WalletRepository $walletRepository,
        private TransactionRepository $transactionRepository
    ) {}

    /**
     * Execute getting wallet details.
     *
     * @param int $walletId
     * @return array|null
     */
    public function execute(...$arguments): ?array
    {
        if (empty($arguments) || !is_int($arguments[0])) {
            throw new \InvalidArgumentException('Wallet ID must be provided as an integer');
        }
        
        $walletId = (int) $arguments[0];
        
        $wallet = $this->walletRepository->findById($walletId);

        if (!$wallet) {
            return null;
        }

        $summary = $this->transactionRepository->getWalletTransactionSummary($walletId);

        return [
            'wallet' => $wallet,
            'balance' => round((float) $wallet->balance, 2),
            'transaction_summary' => $summary,
        ];
    }
}

