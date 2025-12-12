<?php

namespace Tests\Feature;

use App\Models\Transfer;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'VG@123';

    public function test_initiates_transfer_successfully(): void
    {
        $sender = Wallet::factory()->create(['balance' => 500]);
        $receiver = Wallet::factory()->create(['balance' => 100]);

        $response = $this->withHeader('token', $this->token)
            ->postJson('/api/transfers', [
                'sender_wallet_id' => $sender->id,
                'receiver_wallet_id' => $receiver->id,
                'amount' => 200,
                'description' => 'Test transfer'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['transfer_id', 'reference', 'sender_wallet_id', 'receiver_wallet_id', 'amount', 'status']
            ]);

        $sender->refresh();
        $receiver->refresh();

        $this->assertEquals(300, $sender->balance);
        $this->assertEquals(300, $receiver->balance);
    }

    public function test_prevents_same_wallet_transfer(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 500]);

        $response = $this->withHeader('token', $this->token)
            ->postJson('/api/transfers', [
                'sender_wallet_id' => $wallet->id,
                'receiver_wallet_id' => $wallet->id,
                'amount' => 100
            ]);

        $response->assertStatus(422);
    }

    public function test_prevents_insufficient_balance_transfer(): void
    {
        $sender = Wallet::factory()->create(['balance' => 50]);
        $receiver = Wallet::factory()->create();

        $response = $this->withHeader('token', $this->token)
            ->postJson('/api/transfers', [
                'sender_wallet_id' => $sender->id,
                'receiver_wallet_id' => $receiver->id,
                'amount' => 100
            ]);

        $response->assertStatus(400);
    }

    public function test_validates_minimum_amount(): void
    {
        $sender = Wallet::factory()->create(['balance' => 500]);
        $receiver = Wallet::factory()->create();

        $response = $this->withHeader('token', $this->token)
            ->postJson('/api/transfers', [
                'sender_wallet_id' => $sender->id,
                'receiver_wallet_id' => $receiver->id,
                'amount' => 0.5
            ]);

        $response->assertStatus(422);
    }

    public function test_retrieves_transfer_details(): void
    {
        $transfer = Transfer::factory()->create();

        $response = $this->withHeader('token', $this->token)
            ->getJson("/api/transfers/{$transfer->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'transfer_id',
                    'reference',
                    'sender',
                    'receiver',
                    'amount',
                    'status'
                ]
            ]);
    }

    public function test_retrieves_wallet_transfers(): void
    {
        $wallet = Wallet::factory()->create();

        $response = $this->withHeader('token', $this->token)
            ->getJson("/api/wallets/{$wallet->id}/transfers");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'reference', 'type', 'amount', 'sender', 'receiver', 'status']
                ]
            ]);
    }
}

