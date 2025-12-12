<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['id' => 1],
            array_merge(User::factory()->definition(), ['id' => 1, 'password' => Hash::make('password')])
        );
        
        User::firstOrCreate(
            ['id' => 2],
            array_merge(User::factory()->definition(), ['id' => 2, 'password' => Hash::make('password')])
        );
        
        User::firstOrCreate(
            ['id' => 3],
            array_merge(User::factory()->definition(), ['id' => 3, 'password' => Hash::make('password')])
        );

        $wallets = [];
        $now = now();

        $wallets[] = [
            'user_id' => 2,
            'balance' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $wallets[] = [
            'user_id' => 3,
            'balance' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        Wallet::insert($wallets);

        $createdWallets = Wallet::whereIn('user_id', [2, 3])
            ->with('user')
            ->get();

        $transactions = [];
        $now = now();

        foreach ($createdWallets as $wallet) {
            $transactionCount = fake()->numberBetween(3, 8);
            $creditTotal = 0;
            $debitTotal = 0;
            
            for ($i = 0; $i < $transactionCount; $i++) {
                $type = fake()->randomElement(['credit', 'debit']);
                $amount = round(fake()->randomFloat(2, 10, 500), 2);
                
                if ($type === 'credit') {
                    $creditTotal += $amount;
                } else {
                    $debitTotal += $amount;
                }
                
                $transactions[] = [
                    'wallet_id' => $wallet->id,
                    'type' => $type,
                    'amount' => $amount,
                    'reference' => (string) \Illuminate\Support\Str::uuid(),
                    'description' => fake()->sentence(),
                    'status' => 'completed',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            
            $calculatedBalance = round($creditTotal - $debitTotal, 2);
            
            if ($calculatedBalance < 0) {
                $additionalCredit = round(abs($calculatedBalance) + fake()->randomFloat(2, 100, 500), 2);
                $creditTotal += $additionalCredit;
                $calculatedBalance = round($creditTotal - $debitTotal, 2);
                
                $transactions[] = [
                    'wallet_id' => $wallet->id,
                    'type' => 'credit',
                    'amount' => $additionalCredit,
                    'reference' => (string) \Illuminate\Support\Str::uuid(),
                    'description' => fake()->sentence(),
                    'status' => 'completed',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            
            $wallet->update(['balance' => $calculatedBalance]);
        }

        if (!empty($transactions)) {
            Transaction::insert($transactions);
        }
    }
}
