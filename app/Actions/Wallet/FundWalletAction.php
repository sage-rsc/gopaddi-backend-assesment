<?php

namespace App\Actions\Wallet;

use App\Actions\Action;
use App\Exceptions\WalletException;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Traits\Auditable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FundWalletAction extends Action
{
    use Auditable;

    public function __construct(
        private WalletRepository $walletRepository,
        private TransactionRepository $transactionRepository
    ) {}

    /**
     * Execute wallet funding.
     *
     * @param int $walletId
     * @param float $amount
     * @param string|null $description
     * @return Transaction
     * @throws WalletException
     */
    public function execute(...$arguments): Transaction
    {
        if (count($arguments) < 2 || !is_int($arguments[0]) || !is_numeric($arguments[1])) {
            throw new \InvalidArgumentException('Wallet ID and amount must be provided');
        }
        
        $walletId = (int) $arguments[0];
        $amount = (float) $arguments[1];
        $description = $arguments[2] ?? null;
        
        return DB::transaction(function () use ($walletId, $amount, $description) {
            $wallet = $this->walletRepository->findByIdWithLock($walletId);

            if (!$wallet) {
                throw WalletException::walletNotFound($walletId);
            }

            if ($amount <= 0) {
                throw WalletException::invalidAmount();
            }

            // Validate maximum amount to prevent overflow
            $maxAmount = 999999999999.99;
            if ($amount > $maxAmount) {
                throw WalletException::amountExceedsMaximum($maxAmount);
            }

            // Round amount to 2 decimal places for precision
            $amount = round($amount, 2);

            $oldBalance = (float) $wallet->balance;
            $newBalance = round($oldBalance + $amount, 2);

            // Validate balance won't exceed maximum (999,999,999,999.99)
            if ($newBalance > $maxAmount) {
                throw WalletException::balanceExceedsMaximum($maxAmount);
            }

            $this->walletRepository->updateBalance($wallet, $newBalance);

            $transaction = $this->transactionRepository->create([
                'wallet_id' => $walletId,
                'type' => 'credit',
                'amount' => $amount,
                'description' => $description,
                'status' => 'completed',
            ]);

            // Audit log
            $this->audit()->log(
                'transaction',
                'wallet_funded',
                'Transaction',
                $transaction->id,
                [
                    'reference' => $transaction->reference,
                    'wallet_id' => $walletId,
                    'user_id' => $wallet->user_id,
                    'old_values' => ['balance' => $oldBalance],
                    'new_values' => ['balance' => $newBalance, 'amount' => $amount],
                    'status' => 'success',
                ],
                $this->getRequest()
            );

            Log::info("Wallet funded successfully", [
                'wallet_id' => $walletId,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }
}

