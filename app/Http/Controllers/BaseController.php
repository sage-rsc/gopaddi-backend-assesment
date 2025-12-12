<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class BaseController extends Controller
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Handle exceptions and return appropriate JSON response.
     *
     * @param \Exception $e
     * @param string $defaultMessage
     * @return JsonResponse
     */
    protected function handleException(\Exception $e, string $defaultMessage = 'An error occurred'): JsonResponse
    {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        $message = $e->getMessage() ?: $defaultMessage;

        // Log the exception for debugging
        Log::error('Controller exception', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        // Don't expose internal errors in production
        if (config('app.debug')) {
            return $this->errorResponse($message, $statusCode);
        }

        return $this->errorResponse($defaultMessage, $statusCode);
    }
}

