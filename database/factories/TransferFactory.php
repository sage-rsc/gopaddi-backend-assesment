<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transfer>
 */
class TransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_wallet_id' => \App\Models\Wallet::factory(),
            'receiver_wallet_id' => \App\Models\Wallet::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'reference' => (string) \Illuminate\Support\Str::uuid(),
            'description' => fake()->sentence(),
            'status' => 'completed',
        ];
    }
}
