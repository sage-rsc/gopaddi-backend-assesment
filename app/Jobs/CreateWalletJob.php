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

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CreateWalletAction $action): void
    {
        try {
            Log::info("Processing wallet creation job", [
                'user_id' => $this->userId,
                'job_id' => $this->job->getJobId(),
            ]);

            $wallet = $action->handle($this->userId);

            Log::info("Wallet creation job completed successfully", [
                'user_id' => $this->userId,
                'wallet_id' => $wallet->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Wallet creation job failed", [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Wallet creation job permanently failed", [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
