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

class WithdrawWalletAction extends Action
{
    use Auditable;

    public function __construct(
        private WalletRepository $walletRepository,
        private TransactionRepository $transactionRepository
    ) {}

    /**
     * Execute wallet withdrawal.
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

            // Use proper decimal comparison - check if balance is less than amount
            $walletBalance = (float) $wallet->balance;
            if ($walletBalance < $amount) {
                throw WalletException::insufficientBalance($walletBalance, $amount);
            }

            $oldBalance = (float) $wallet->balance;
            $newBalance = round($oldBalance - $amount, 2);

            // Defensive check: ensure balance won't go negative
            if ($newBalance < 0) {
                throw WalletException::insufficientBalance($wallet->balance, $amount);
            }

            $this->walletRepository->updateBalance($wallet, $newBalance);

            $transaction = $this->transactionRepository->create([
                'wallet_id' => $walletId,
                'type' => 'debit',
                'amount' => $amount,
                'description' => $description,
                'status' => 'completed',
            ]);

            // Audit log
            $this->audit()->log(
                'transaction',
                'wallet_withdrawn',
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

            Log::info("Wallet withdrawal successful", [
                'wallet_id' => $walletId,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }
}

