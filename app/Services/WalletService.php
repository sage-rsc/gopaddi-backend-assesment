<?php

namespace App\Services;

use App\Actions\Wallet\CreateWalletAction;
use App\Actions\Wallet\DeleteWalletAction;
use App\Actions\Wallet\FundWalletAction;
use App\Actions\Wallet\GetWalletDetailsAction;
use App\Actions\Wallet\WithdrawWalletAction;
use App\Jobs\CreateWalletJob;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public function __construct(
        private CreateWalletAction $createWalletAction,
        private GetWalletDetailsAction $getWalletDetailsAction,
        private FundWalletAction $fundWalletAction,
        private WithdrawWalletAction $withdrawWalletAction,
        private DeleteWalletAction $deleteWalletAction
    ) {}

    /**
     * Dispatch wallet creation asynchronously.
     *
     * @param int $userId
     * @return void
     */
    public function createWalletAsync(int $userId): void
    {
        Log::info("Dispatching wallet creation job", [
            'user_id' => $userId,
        ]);

        CreateWalletJob::dispatch($userId);
    }

    /**
     * Create wallet synchronously (for internal use).
     *
     * @param int $userId
     * @return Wallet
     */
    public function createWallet(int $userId): Wallet
    {
        return $this->createWalletAction->handle($userId);
    }

    /**
     * Get wallet details.
     *
     * @param int $walletId
     * @return array|null
     */
    public function getWalletDetails(int $walletId): ?array
    {
        return $this->getWalletDetailsAction->handle($walletId);
    }

    /**
     * Fund wallet.
     *
     * @param int $walletId
     * @param float $amount
     * @param string|null $description
     * @return Transaction
     */
    public function fundWallet(int $walletId, float $amount, ?string $description = null): Transaction
    {
        return $this->fundWalletAction->handle($walletId, $amount, $description);
    }

    /**
     * Withdraw from wallet.
     *
     * @param int $walletId
     * @param float $amount
     * @param string|null $description
     * @return Transaction
     */
    public function withdrawFromWallet(int $walletId, float $amount, ?string $description = null): Transaction
    {
        return $this->withdrawWalletAction->handle($walletId, $amount, $description);
    }

    /**
     * Delete wallet.
     *
     * @param int $walletId
     * @return bool
     */
    public function deleteWallet(int $walletId): bool
    {
        return $this->deleteWalletAction->handle($walletId);
    }
}

