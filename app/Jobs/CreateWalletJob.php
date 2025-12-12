<?php

namespace App\Jobs;

use App\Actions\Wallet\CreateWalletAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateWalletJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    public function __construct(
        public int $userId
    ) {}

    public function handle(CreateWalletAction $action): void
    {
        // TODO: Implement virtual account creation with payment provider
        // This job is reserved for future implementation where wallet creation
        // will trigger virtual account creation with external payment providers
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Virtual account creation job failed", [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
