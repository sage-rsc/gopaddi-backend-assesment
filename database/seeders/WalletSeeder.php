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

        $balance2 = fake()->randomFloat(2, 100, 5000);
        $wallets[] = [
            'id' => 2,
            'user_id' => 2,
            'balance' => $balance2,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $balance3 = fake()->randomFloat(2, 100, 5000);
        $wallets[] = [
            'id' => 3,
            'user_id' => 3,
            'balance' => $balance3,
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

        if (!empty($transactions)) {
            Transaction::insert($transactions);
        }
    }
}
