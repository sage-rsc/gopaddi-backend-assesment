<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);

        // Use atomic increment to prevent race conditions
        $attempts = Cache::increment($key);
        
        // If key didn't exist, increment returns false, so initialize it
        if ($attempts === false) {
            Cache::put($key, 1, now()->addMinutes($decayMinutes));
            $attempts = 1;
        } elseif ($attempts === 1) {
            // First request after initialization - ensure expiration is set
            Cache::put($key, 1, now()->addMinutes($decayMinutes));
        }

        if ($attempts > $maxAttempts) {
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
                'attempts' => $attempts,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $decayMinutes * 60,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addMinutes($decayMinutes)->timestamp,
            ]);
        }

        $response = $next($request);

        // Add rate limit headers
        $remaining = max(0, $maxAttempts - $attempts);
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', now()->addMinutes($decayMinutes)->timestamp);

        return $response;
    }

    /**
     * Resolve request signature for rate limiting.
     *
     * @param Request $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // Use IP address and endpoint as key
        return 'rate_limit:' . $request->ip() . ':' . $request->path();
    }
}

