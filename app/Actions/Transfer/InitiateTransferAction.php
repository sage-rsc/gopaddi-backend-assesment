<?php

namespace App\Actions\Transfer;

use App\Actions\Action;
use App\Exceptions\TransferException;
use App\Models\Transfer;
use App\Repositories\TransactionRepository;
use App\Repositories\TransferRepository;
use App\Repositories\WalletRepository;
use App\Traits\Auditable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InitiateTransferAction extends Action
{
    use Auditable;

    public function __construct(
        private readonly TransferRepository $transferRepository,
        private readonly WalletRepository   $walletRepository,
        private readonly TransactionRepository $transactionRepository
    ) {}

    /**
     * Execute transfer initiation.
     *
     * @param int $senderWalletId
     * @param int $receiverWalletId
     * @param float $amount
     * @param string|null $description
     * @return Transfer
     * @throws TransferException
     */
    public function execute(...$arguments): Transfer
    {
        if (count($arguments) < 3 || !is_int($arguments[0]) || !is_int($arguments[1]) || !is_numeric($arguments[2])) {
            throw new \InvalidArgumentException('Sender wallet ID, receiver wallet ID, and amount must be provided');
        }
        
        $senderWalletId = (int) $arguments[0];
        $receiverWalletId = (int) $arguments[1];
        $amount = (float) $arguments[2];
        $description = $arguments[3] ?? null;
        
        return DB::transaction(function () use ($senderWalletId, $receiverWalletId, $amount, $description) {
            $walletIds = [$senderWalletId, $receiverWalletId];
            sort($walletIds);
            
            $firstWallet = $this->walletRepository->findByIdWithLock($walletIds[0]);
            $secondWallet = $this->walletRepository->findByIdWithLock($walletIds[1]);
            
            if ($walletIds[0] === $senderWalletId) {
                $senderWallet = $firstWallet;
                $receiverWallet = $secondWallet;
            } else {
                $senderWallet = $secondWallet;
                $receiverWallet = $firstWallet;
            }

            if (!$senderWallet) {
                throw TransferException::senderWalletNotFound($senderWalletId);
            }

            if (!$receiverWallet) {
                throw TransferException::receiverWalletNotFound($receiverWalletId);
            }

            if ($senderWalletId === $receiverWalletId) {
                throw TransferException::sameWallet();
            }

            if ($amount < 1) {
                throw TransferException::invalidAmount();
            }

            $maxAmount = 999999999999.99;
            if ($amount > $maxAmount) {
                throw TransferException::amountExceedsMaximum($maxAmount);
            }

            $amount = round($amount, 2);
            $senderBalance = (float) $senderWallet->balance;
            if ($senderBalance < $amount) {
                throw TransferException::insufficientBalance($senderBalance, $amount);
            }

            $transfer = $this->transferRepository->create([
                'sender_wallet_id' => $senderWalletId,
                'receiver_wallet_id' => $receiverWalletId,
                'amount' => $amount,
                'description' => $description,
                'status' => 'pending',
            ]);

            try {
                $senderNewBalance = round((float) $senderWallet->balance - $amount, 2);
                $receiverNewBalance = round((float) $receiverWallet->balance + $amount, 2);

                if ($senderNewBalance < 0) {
                    throw TransferException::insufficientBalance($senderWallet->balance, $amount);
                }

                $maxBalance = 999999999999.99;
                if ($receiverNewBalance > $maxBalance) {
                    throw TransferException::receiverBalanceExceedsMaximum($maxBalance);
                }

                $this->walletRepository->updateBalance($senderWallet, $senderNewBalance);
                $this->walletRepository->updateBalance($receiverWallet, $receiverNewBalance);
                
                $senderWallet->refresh();
                $receiverWallet->refresh();
                $transactions = [
                    [
                        'wallet_id' => $senderWalletId,
                        'type' => 'transfer_out',
                        'amount' => $amount,
                        'reference' => (string) Str::uuid(),
                        'description' => $description,
                        'status' => 'completed',
                        'transfer_id' => $transfer->id,
                    ],
                    [
                        'wallet_id' => $receiverWalletId,
                        'type' => 'transfer_in',
                        'amount' => $amount,
                        'reference' => (string) Str::uuid(),
                        'description' => $description,
                        'status' => 'completed',
                        'transfer_id' => $transfer->id,
                    ],
                ];

                $this->transactionRepository->bulkCreate($transactions);

                $transfer->update(['status' => 'completed']);

                $this->audit()->log(
                    'transfer',
                    'transfer_completed',
                    'Transfer',
                    $transfer->id,
                    [
                        'reference' => $transfer->reference,
                        'sender_wallet_id' => $senderWalletId,
                        'receiver_wallet_id' => $receiverWalletId,
                        'user_id' => $senderWallet->user_id,
                        'old_values' => [
                            'sender_balance' => $senderWallet->balance,
                            'receiver_balance' => $receiverWallet->balance,
                        ],
                        'new_values' => [
                            'sender_balance' => $senderNewBalance,
                            'receiver_balance' => $receiverNewBalance,
                            'amount' => $amount,
                        ],
                        'status' => 'success',
                    ],
                    $this->getRequest()
                );

                Log::info("Transfer completed successfully", [
                    'transfer_id' => $transfer->id,
                    'reference' => $transfer->reference,
                    'sender_wallet_id' => $senderWalletId,
                    'receiver_wallet_id' => $receiverWalletId,
                    'amount' => $amount,
                ]);

                return $transfer->fresh(['senderWallet.user', 'receiverWallet.user']);
            } catch (\Exception $e) {
                $transfer->update(['status' => 'failed']);

                $this->audit()->log(
                    'transfer',
                    'transfer_failed',
                    'Transfer',
                    $transfer->id,
                    [
                        'reference' => $transfer->reference,
                        'sender_wallet_id' => $senderWalletId,
                        'receiver_wallet_id' => $receiverWalletId,
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ],
                    $this->getRequest()
                );

                Log::error("Transfer failed", [
                    'transfer_id' => $transfer->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }
}

