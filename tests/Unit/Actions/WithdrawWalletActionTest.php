<?php

namespace Tests\Unit\Actions;

use App\Actions\Wallet\WithdrawWalletAction;
use App\Exceptions\WalletException;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawWalletActionTest extends TestCase
{
    use RefreshDatabase;

    private WithdrawWalletAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new WithdrawWalletAction(
            new WalletRepository(),
            new TransactionRepository(),
            new AuditService()
        );
    }

    public function test_withdraws_from_wallet_successfully(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 200]);

        $transaction = $this->action->handle($wallet->id, 50, 'Test withdrawal');

        $this->assertEquals('debit', $transaction->type);
        $this->assertEquals(50, $transaction->amount);

        $wallet->refresh();
        $this->assertEquals(150, $wallet->balance);
    }

    public function test_prevents_insufficient_balance(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 50]);

        $this->expectException(WalletException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->action->handle($wallet->id, 100, 'Test');
    }

    public function test_prevents_negative_balance(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 50]);

        $this->expectException(WalletException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->action->handle($wallet->id, 51, 'Test');
    }

    public function test_throws_exception_for_invalid_amount(): void
    {
        $wallet = Wallet::factory()->create();

        $this->expectException(WalletException::class);
        $this->expectExceptionMessage('Invalid amount');

        $this->action->handle($wallet->id, 0.5, 'Test');
    }
}

