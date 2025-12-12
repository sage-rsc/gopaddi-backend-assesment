<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Wallet;
use App\Repositories\TransactionRepository;
use App\Repositories\TransferRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LedgerService
{
    public function __construct(
        private WalletRepository $walletRepository,
        private TransactionRepository $transactionRepository,
        private TransferRepository $transferRepository,
        private AuditService $auditService
    ) {}

    /**
     * Verify ledger integrity for a wallet.
     *
     * @param int $walletId
     * @return array
     */
    public function verifyWalletIntegrity(int $walletId): array
    {
        $wallet = $this->walletRepository->findById($walletId);

        if (!$wallet) {
            return [
                'valid' => false,
                'error' => 'Wallet not found',
            ];
        }

        // Calculate expected balance from transactions
        $summary = $this->transactionRepository->getWalletTransactionSummary($walletId);
        $expectedBalance = $summary['credit_total'] - $summary['debit_total'];

        // Compare with actual balance using proper decimal comparison
        // Allow for minimal rounding differences (0.01) due to decimal precision
        $difference = abs((float) $wallet->balance - $expectedBalance);
        $balanceMatch = $difference < 0.01;

        $result = [
            'valid' => $balanceMatch,
            'wallet_id' => $walletId,
            'actual_balance' => (float) $wallet->balance,
            'calculated_balance' => $expectedBalance,
            'difference' => abs($wallet->balance - $expectedBalance),
            'credit_total' => $summary['credit_total'],
            'debit_total' => $summary['debit_total'],
        ];

        if (!$balanceMatch) {
            Log::critical('Ledger integrity check failed', $result);
            
            $this->auditService->log(
                'ledger',
                'integrity_check_failed',
                'Wallet',
                $walletId,
                [
                    'wallet_id' => $walletId,
                    'status' => 'failed',
                    'error_message' => 'Balance mismatch detected',
                    'new_values' => $result,
                ]
            );
        }

        return $result;
    }

    /**
     * Verify transfer integrity (double-entry bookkeeping).
     *
     * @param int $transferId
     * @return array
     */
    public function verifyTransferIntegrity(int $transferId): array
    {
        $transfer = $this->transferRepository->findById($transferId);

        if (!$transfer) {
            return [
                'valid' => false,
                'error' => 'Transfer not found',
            ];
        }

        // Get related transactions
        $transactions = Transaction::where('transfer_id', $transferId)
            ->where('status', 'completed')
            ->get();

        $debitTransaction = $transactions->where('type', 'transfer_out')->first();
        $creditTransaction = $transactions->where('type', 'transfer_in')->first();

        $valid = true;
        $errors = [];

        // Check both transactions exist
        if (!$debitTransaction) {
            $valid = false;
            $errors[] = 'Debit transaction missing';
        }

        if (!$creditTransaction) {
            $valid = false;
            $errors[] = 'Credit transaction missing';
        }

        // Check amounts match using proper decimal comparison
        if ($debitTransaction && $creditTransaction) {
            $debitAmount = (float) $debitTransaction->amount;
            $creditAmount = (float) $creditTransaction->amount;
            $transferAmount = (float) $transfer->amount;

            // Compare with tolerance for decimal precision
            if (abs($debitAmount - $creditAmount) >= 0.01) {
                $valid = false;
                $errors[] = 'Transaction amounts do not match';
            }

            if (abs($debitAmount - $transferAmount) >= 0.01) {
                $valid = false;
                $errors[] = 'Transfer amount does not match transaction amounts';
            }

            // Verify both transactions are linked to the same transfer
            if ($debitTransaction->transfer_id !== $creditTransaction->transfer_id || 
                $debitTransaction->transfer_id !== $transferId) {
                $valid = false;
                $errors[] = 'Transactions are not properly linked to the transfer';
            }
        }

        $result = [
            'valid' => $valid,
            'transfer_id' => $transferId,
            'transfer_reference' => $transfer->reference,
            'transfer_amount' => (float) $transfer->amount,
            'debit_transaction' => $debitTransaction ? [
                'id' => $debitTransaction->id,
                'amount' => (float) $debitTransaction->amount,
                'reference' => $debitTransaction->reference,
            ] : null,
            'credit_transaction' => $creditTransaction ? [
                'id' => $creditTransaction->id,
                'amount' => (float) $creditTransaction->amount,
                'reference' => $creditTransaction->reference,
            ] : null,
            'errors' => $errors,
        ];

        if (!$valid) {
            Log::critical('Transfer integrity check failed', $result);
            
            $this->auditService->log(
                'ledger',
                'integrity_check_failed',
                'Transfer',
                $transferId,
                [
                    'transfer_id' => $transferId,
                    'status' => 'failed',
                    'error_message' => implode(', ', $errors),
                    'new_values' => $result,
                ]
            );
        }

        return $result;
    }

    /**
     * Get complete ledger audit trail for a wallet.
     *
     * @param int $walletId
     * @return array
     */
    public function getLedgerAuditTrail(int $walletId): array
    {
        $wallet = $this->walletRepository->findById($walletId);

        if (!$wallet) {
            return [];
        }

        $transactions = $this->transactionRepository->findByWalletId($walletId);
        $transfers = $this->transferRepository->findByWalletId($walletId);

        return [
            'wallet' => [
                'id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'balance' => (float) $wallet->balance,
            ],
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'reference' => $transaction->reference,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at,
                ];
            }),
            'transfers' => $transfers->map(function ($transfer) {
                return [
                    'id' => $transfer->id,
                    'reference' => $transfer->reference,
                    'amount' => (float) $transfer->amount,
                    'direction' => $transfer->sender_wallet_id === $walletId ? 'outgoing' : 'incoming',
                    'status' => $transfer->status,
                    'created_at' => $transfer->created_at,
                ];
            }),
            'integrity_check' => $this->verifyWalletIntegrity($walletId),
        ];
    }
}

