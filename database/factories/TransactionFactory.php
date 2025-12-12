<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['credit', 'debit', 'transfer_in', 'transfer_out'];
        
        return [
            'wallet_id' => \App\Models\Wallet::factory(),
            'type' => fake()->randomElement($types),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'reference' => (string) \Illuminate\Support\Str::uuid(),
            'description' => fake()->sentence(),
            'status' => 'completed',
        ];
    }
}
