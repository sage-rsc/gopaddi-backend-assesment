<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Log an audit event.
     *
     * @param string $eventType
     * @param string $action
     * @param string $entityType
     * @param int|null $entityId
     * @param array $data
     * @param Request|null $request
     * @return AuditLog
     */
    public function log(
        string $eventType,
        string $action,
        string $entityType,
        ?int $entityId = null,
        array $data = [],
        ?Request $request = null
    ): AuditLog {
        try {
            $metadata = [];
            $ipAddress = null;
            $userAgent = null;

            if ($request) {
                $ipAddress = $request->ip();
                $userAgent = $request->userAgent();
                $metadata = [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'headers' => $this->sanitizeHeaders($request->headers->all()),
                ];
            }

            return AuditLog::create([
                'event_type' => $eventType,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'reference' => $data['reference'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'wallet_id' => $data['wallet_id'] ?? null,
                'old_values' => $data['old_values'] ?? null,
                'new_values' => $data['new_values'] ?? null,
                'metadata' => $metadata,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'status' => $data['status'] ?? 'success',
                'error_message' => $data['error_message'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Audit logging failed', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'action' => $action,
            ]);

            return new AuditLog();
        }
    }

    public function bulkLog(array $events): int
    {
        try {
            $logs = [];
            $now = now();

            foreach ($events as $event) {
                $logs[] = [
                    'event_type' => $event['event_type'],
                    'action' => $event['action'],
                    'entity_type' => $event['entity_type'],
                    'entity_id' => $event['entity_id'] ?? null,
                    'reference' => $event['reference'] ?? null,
                    'user_id' => $event['user_id'] ?? null,
                    'wallet_id' => $event['wallet_id'] ?? null,
                    'old_values' => isset($event['old_values']) ? json_encode($event['old_values']) : null,
                    'new_values' => isset($event['new_values']) ? json_encode($event['new_values']) : null,
                    'metadata' => isset($event['metadata']) ? json_encode($event['metadata']) : null,
                    'ip_address' => $event['ip_address'] ?? null,
                    'user_agent' => $event['user_agent'] ?? null,
                    'status' => $event['status'] ?? 'success',
                    'error_message' => $event['error_message'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            return AuditLog::insert($logs);
        } catch (\Exception $e) {
            Log::error('Bulk audit logging failed', [
                'error' => $e->getMessage(),
                'count' => count($events),
            ]);

            return 0;
        }
    }

    private function sanitizeHeaders(array $headers): array
    {
        $sensitive = ['authorization', 'cookie', 'x-api-key', 'token'];
        $sanitized = [];

        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, $sensitive)) {
                $sanitized[$key] = ['***REDACTED***'];
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}

