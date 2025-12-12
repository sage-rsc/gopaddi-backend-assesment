<?php

namespace Tests\Unit\Actions;

use App\Actions\Wallet\CreateWalletAction;
use App\Exceptions\WalletException;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\WalletRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateWalletActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateWalletAction $action;
    private WalletRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new WalletRepository();
        $this->action = new CreateWalletAction($this->repository);
    }

    public function test_creates_wallet_successfully(): void
    {
        $user = User::factory()->create();

        $wallet = $this->action->handle($user->id);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals($user->id, $wallet->user_id);
        $this->assertEquals(0, $wallet->balance);
    }

    public function test_prevents_duplicate_wallet_creation(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->create(['user_id' => $user->id]);

        $this->expectException(WalletException::class);
        $this->expectExceptionMessage('User already has a wallet');

        $this->action->handle($user->id);
    }

    public function test_throws_exception_for_nonexistent_user(): void
    {
        $this->expectException(WalletException::class);
        $this->expectExceptionMessage('User not found');

        $this->action->handle(99999);
    }
}

