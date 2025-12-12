<?php

namespace App\Actions;

use Illuminate\Support\Facades\Log;

abstract class Action
{
    /**
     * Execute the action.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    abstract public function execute(...$arguments);

    /**
     * Handle the action execution with error handling and logging.
     *
     * @param mixed ...$arguments
     * @return mixed
     * @throws \Exception
     */
    public function handle(...$arguments)
    {
        $actionName = static::class;

        try {
            Log::info("Action started: {$actionName}", [
                'arguments' => $this->sanitizeArguments($arguments),
            ]);

            $result = $this->execute(...$arguments);

            Log::info("Action completed: {$actionName}", [
                'result_type' => gettype($result),
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error("Action failed: {$actionName}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Sanitize arguments for logging (remove sensitive data).
     *
     * @param array $arguments
     * @return array
     */
    protected function sanitizeArguments(array $arguments): array
    {
        return $arguments;
    }
}

