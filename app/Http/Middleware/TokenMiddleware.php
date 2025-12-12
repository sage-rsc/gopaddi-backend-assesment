<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('token') ?? $request->input('token');

        if (!$token || $token !== 'VG@123') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or missing token.',
            ], 401);
        }

        return $next($request);
    }
}
