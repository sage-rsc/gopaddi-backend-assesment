<?php

namespace Tests\Unit\Actions;

use App\Actions\Wallet\FundWalletAction;
use App\Exceptions\WalletException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundWalletActionTest extends TestCase
{
    use RefreshDatabase;

    private FundWalletAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new FundWalletAction(
            new WalletRepository(),
            new TransactionRepository(),
            new AuditService()
        );
    }

    public function test_funds_wallet_successfully(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100]);

        $transaction = $this->action->handle($wallet->id, 50, 'Test funding');

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('credit', $transaction->type);
        $this->assertEquals(50, $transaction->amount);
        $this->assertEquals('completed', $transaction->status);

        $wallet->refresh();
        $this->assertEquals(150, $wallet->balance);
    }

    public function test_throws_exception_for_invalid_amount(): void
    {
        $wallet = Wallet::factory()->create();

        $this->expectException(WalletException::class);
        $this->expectExceptionMessage('Invalid amount');

        $this->action->handle($wallet->id, 0.5, 'Test');
    }

    public function test_throws_exception_for_nonexistent_wallet(): void
    {
        $this->expectException(WalletException::class);
        $this->expectExceptionMessage('Wallet not found');

        $this->action->handle(99999, 100, 'Test');
    }
}

