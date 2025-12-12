<?php

namespace App\Actions\Wallet;

use App\Actions\Action;
use App\Exceptions\WalletException;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteWalletAction extends Action
{
    public function __construct(
        private WalletRepository $walletRepository
    ) {}

    /**
     * Execute wallet deletion.
     *
     * @param int $walletId
     * @return bool
     * @throws WalletException
     */
    public function execute(...$arguments): bool
    {
        if (empty($arguments) || !is_int($arguments[0])) {
            throw new \InvalidArgumentException('Wallet ID must be provided as an integer');
        }
        
        $walletId = (int) $arguments[0];
        
        return DB::transaction(function () use ($walletId) {
            $wallet = $this->walletRepository->findByIdWithLock($walletId);

            if (!$wallet) {
                throw WalletException::walletNotFound($walletId);
            }

            $balance = (float) $wallet->balance;
            if ($balance > 0.01) {
                throw WalletException::cannotDeleteNonZeroBalance($balance);
            }

            $deleted = $this->walletRepository->delete($wallet);

            if ($deleted) {
                Log::info("Wallet deleted successfully", [
                    'wallet_id' => $walletId,
                ]);
            }

            return $deleted;
        });
    }
}

