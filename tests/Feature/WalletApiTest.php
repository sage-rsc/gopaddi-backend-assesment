<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'VG@123';

    public function test_creates_wallet_successfully(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeader('token', $this->token)
            ->postJson('/api/wallets', ['user_id' => $user->id]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['wallet_id', 'user_id', 'balance', 'created_at']
            ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 0
        ]);
    }

    public function test_prevents_duplicate_wallet_creation(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeader('token', $this->token)
            ->postJson('/api/wallets', ['user_id' => $user->id]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_requires_token_authentication(): void
    {
        $response = $this->postJson('/api/wallets', ['user_id' => 1]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_funds_wallet_successfully(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 100]);

        $response = $this->withHeader('token', $this->token)
            ->postJson("/api/wallets/{$wallet->id}/fund", [
                'amount' => 50,
                'description' => 'Test funding'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['transaction_id', 'reference', 'amount', 'type', 'status']
            ]);

        $wallet->refresh();
        $this->assertEquals(150, $wallet->balance);
    }

    public function test_validates_minimum_amount(): void
    {
        $wallet = Wallet::factory()->create();

        $response = $this->withHeader('token', $this->token)
            ->postJson("/api/wallets/{$wallet->id}/fund", [
                'amount' => 0.5
            ]);

        $response->assertStatus(422);
    }

    public function test_withdraws_from_wallet_successfully(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 200]);

        $response = $this->withHeader('token', $this->token)
            ->postJson("/api/wallets/{$wallet->id}/withdraw", [
                'amount' => 50,
                'description' => 'Test withdrawal'
            ]);

        $response->assertStatus(200);

        $wallet->refresh();
        $this->assertEquals(150, $wallet->balance);
    }

    public function test_prevents_insufficient_balance_withdrawal(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 50]);

        $response = $this->withHeader('token', $this->token)
            ->postJson("/api/wallets/{$wallet->id}/withdraw", [
                'amount' => 100
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_deletes_wallet_with_zero_balance(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 0]);

        $response = $this->withHeader('token', $this->token)
            ->deleteJson("/api/wallets/{$wallet->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
    }

    public function test_prevents_deleting_wallet_with_balance(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 100]);

        $response = $this->withHeader('token', $this->token)
            ->deleteJson("/api/wallets/{$wallet->id}");

        $response->assertStatus(400);
    }

    public function test_retrieves_wallet_details(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 500]);

        $response = $this->withHeader('token', $this->token)
            ->getJson("/api/wallets/{$wallet->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'wallet_id',
                    'user_id',
                    'balance',
                    'transaction_summary' => ['credit_total', 'debit_total']
                ]
            ]);
    }
}

