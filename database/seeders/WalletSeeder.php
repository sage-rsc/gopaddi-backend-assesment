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
        // Create user_id 1 without wallet (for testing wallet creation)
        User::firstOrCreate(
            ['id' => 1],
            User::factory()->make(['id' => 1])->toArray()
        );
        
        // Create user_id 2 and 3 with wallets
        $user2 = User::firstOrCreate(
            ['id' => 2],
            User::factory()->make(['id' => 2])->toArray()
        );
        
        $user3 = User::firstOrCreate(
            ['id' => 3],
            User::factory()->make(['id' => 3])->toArray()
        );

        // Create wallets for user 2 and 3 only
        $wallets = [];
        $now = now();

        $balance2 = fake()->randomFloat(2, 100, 5000);
        $wallets[] = [
            'user_id' => 2,
            'balance' => $balance2,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $balance3 = fake()->randomFloat(2, 100, 5000);
        $wallets[] = [
            'user_id' => 3,
            'balance' => $balance3,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        Wallet::insert($wallets);

        // Fetch created wallets with relationships
        $createdWallets = Wallet::whereIn('user_id', [2, 3])
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
