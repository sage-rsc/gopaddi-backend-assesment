<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TransactionRepository
{
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function bulkCreate(array $transactions): bool
    {
        if (empty($transactions)) {
            return false;
        }

        $now = now();
        $data = [];
        $requiredFields = ['wallet_id', 'type', 'amount', 'reference', 'status'];

        foreach ($transactions as $index => $transaction) {
            foreach ($requiredFields as $field) {
                if (!isset($transaction[$field])) {
                    throw new \InvalidArgumentException("Missing required field: {$field} in transaction at index {$index}");
                }
            }

            $amount = $transaction['amount'];
            if (!is_numeric($amount) || $amount < 0 || $amount > 999999999999.99) {
                throw new \InvalidArgumentException("Invalid amount in transaction at index {$index}");
            }
            $transaction['amount'] = round((float) $amount, 2);

            if (!is_int($transaction['wallet_id']) && !is_numeric($transaction['wallet_id'])) {
                throw new \InvalidArgumentException("Invalid wallet_id in transaction at index {$index}");
            }
            $transaction['wallet_id'] = (int) $transaction['wallet_id'];

            $allowedTypes = ['credit', 'debit', 'transfer_in', 'transfer_out'];
            if (!in_array($transaction['type'], $allowedTypes, true)) {
                throw new \InvalidArgumentException("Invalid transaction type '{$transaction['type']}' at index {$index}");
            }

            $allowedStatuses = ['pending', 'completed', 'failed'];
            if (!in_array($transaction['status'], $allowedStatuses, true)) {
                throw new \InvalidArgumentException("Invalid transaction status '{$transaction['status']}' at index {$index}");
            }

            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $transaction['reference'])) {
                throw new \InvalidArgumentException("Invalid reference format in transaction at index {$index}");
            }

            if (isset($transaction['transfer_id']) && $transaction['transfer_id'] !== null) {
                if (!is_int($transaction['transfer_id']) && !is_numeric($transaction['transfer_id'])) {
                    throw new \InvalidArgumentException("Invalid transfer_id in transaction at index {$index}");
                }
                $transaction['transfer_id'] = (int) $transaction['transfer_id'];
            } else {
                $transaction['transfer_id'] = null;
            }

            if (isset($transaction['description']) && $transaction['description'] !== null) {
                $transaction['description'] = mb_substr(strip_tags($transaction['description']), 0, 500);
            }

            $data[] = array_merge($transaction, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return Transaction::insert($data);
    }

    public function findByWalletId(int $walletId): Collection
    {
        return Transaction::where('wallet_id', $walletId)
            ->with('wallet.user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByReference(string $reference): ?Transaction
    {
        return Transaction::where('reference', $reference)
            ->with('wallet.user', 'transfer')
            ->first();
    }

    public function getWalletTransactionSummary(int $walletId): array
    {
        $summary = Transaction::where('wallet_id', $walletId)
            ->where('status', 'completed')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN type IN (?, ?) THEN amount ELSE 0 END), 0) as credit_total,
                COALESCE(SUM(CASE WHEN type IN (?, ?) THEN amount ELSE 0 END), 0) as debit_total
            ', ['credit', 'transfer_in', 'debit', 'transfer_out'])
            ->first();

        return [
            'credit_total' => round((float) ($summary->credit_total ?? 0), 2),
            'debit_total' => round((float) ($summary->debit_total ?? 0), 2),
        ];
    }

    public function findByWalletIds(array $walletIds): Collection
    {
        return Transaction::whereIn('wallet_id', $walletIds)
            ->with('wallet.user')
            ->get();
    }
}


