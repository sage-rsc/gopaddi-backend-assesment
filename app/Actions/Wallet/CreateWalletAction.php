<?php

namespace App\Actions\Wallet;

use App\Actions\Action;
use App\Exceptions\WalletException;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateWalletAction extends Action
{
    public function __construct(
        private WalletRepository $walletRepository
    ) {}

    /**
     * Execute wallet creation.
     *
     * @param int $userId
     * @return Wallet
     * @throws WalletException
     */
    public function execute(...$arguments): Wallet
    {
        if (empty($arguments) || !is_int($arguments[0])) {
            throw new \InvalidArgumentException('User ID must be provided as an integer');
        }
        
        $userId = (int) $arguments[0];
        
        return DB::transaction(function () use ($userId) {
            // Check if user already has a wallet (with lock to prevent race condition)
            $existingWallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

            if ($existingWallet) {
                throw WalletException::userAlreadyHasWallet($userId);
            }

            // Validate user exists
            if (!\App\Models\User::find($userId)) {
                throw WalletException::userNotFound($userId);
            }

            $wallet = $this->walletRepository->create([
                'user_id' => $userId,
                'balance' => 0.00,
            ]);

            Log::info("Wallet created successfully", [
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
            ]);

            return $wallet;
        });
    }
}

