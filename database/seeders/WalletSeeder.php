<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 3 test users using bulk insert
        $users = User::factory(3)->create();
        $userIds = $users->pluck('id')->toArray();

        // Bulk create wallets using relationships
        $wallets = [];
        $now = now();

        foreach ($users as $user) {
            $balance = fake()->randomFloat(2, 100, 5000);
            $wallets[] = [
                'user_id' => $user->id,
                'balance' => $balance,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Wallet::insert($wallets);

        // Fetch created wallets with relationships
        $createdWallets = Wallet::whereIn('user_id', $userIds)
            ->with('user')
            ->get();

        // Bulk create transactions (optimized - no loops)
        $transactions = [];
        $now = now();

        foreach ($createdWallets as $wallet) {
            $transactionCount = fake()->numberBetween(3, 8);
            
            for ($i = 0; $i < $transactionCount; $i++) {
                $type = fake()->randomElement(['credit', 'debit']);
                $amount = fake()->randomFloat(2, 10, 500);
                
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
        }

        // Bulk insert all transactions at once
        if (!empty($transactions)) {
            Transaction::insert($transactions);
        }
    }
}
