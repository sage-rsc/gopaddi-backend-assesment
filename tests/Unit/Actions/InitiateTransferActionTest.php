<?php

namespace Tests\Unit\Actions;

use App\Actions\Transfer\InitiateTransferAction;
use App\Exceptions\TransferException;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\TransactionRepository;
use App\Repositories\TransferRepository;
use App\Repositories\WalletRepository;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiateTransferActionTest extends TestCase
{
    use RefreshDatabase;

    private InitiateTransferAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new InitiateTransferAction(
            new TransferRepository(),
            new WalletRepository(),
            new TransactionRepository()
        );
    }

    public function test_transfers_funds_successfully(): void
    {
        $sender = Wallet::factory()->create(['balance' => 500]);
        $receiver = Wallet::factory()->create(['balance' => 100]);

        $transfer = $this->action->handle($sender->id, $receiver->id, 200, 'Test transfer');

        $this->assertInstanceOf(Transfer::class, $transfer);
        $this->assertEquals('completed', $transfer->status);
        $this->assertEquals(200, $transfer->amount);

        $sender->refresh();
        $receiver->refresh();

        $this->assertEquals(300, $sender->balance);
        $this->assertEquals(300, $receiver->balance);
    }

    public function test_prevents_same_wallet_transfer(): void
    {
        $wallet = Wallet::factory()->create();

        $this->expectException(TransferException::class);
        $this->expectExceptionMessage('Sender and receiver wallets must be different');

        $this->action->handle($wallet->id, $wallet->id, 100, 'Test');
    }

    public function test_prevents_insufficient_balance(): void
    {
        $sender = Wallet::factory()->create(['balance' => 50]);
        $receiver = Wallet::factory()->create();

        $this->expectException(TransferException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->action->handle($sender->id, $receiver->id, 100, 'Test');
    }

    public function test_creates_double_entry_transactions(): void
    {
        $sender = Wallet::factory()->create(['balance' => 500]);
        $receiver = Wallet::factory()->create(['balance' => 100]);

        $transfer = $this->action->handle($sender->id, $receiver->id, 200, 'Test');

        $transactions = \App\Models\Transaction::where('transfer_id', $transfer->id)->get();

        $this->assertCount(2, $transactions);
        $this->assertTrue($transactions->contains('type', 'transfer_out'));
        $this->assertTrue($transactions->contains('type', 'transfer_in'));
    }
}

