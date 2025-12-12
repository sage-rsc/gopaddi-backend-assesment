<?php

namespace App\Http\Controllers;

use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;

class LedgerController extends BaseController
{
    public function __construct(
        private LedgerService $ledgerService
    ) {}

    /**
     * Verify wallet ledger integrity.
     *
     * @param int $walletId
     * @return JsonResponse
     */
    public function verifyWallet(int $walletId): JsonResponse
    {
        try {
            $result = $this->ledgerService->verifyWalletIntegrity($walletId);

            if (!$result['valid']) {
                return $this->errorResponse(
                    $result['error'] ?? 'Ledger integrity check failed',
                    400,
                    $result
                );
            }

            return $this->successResponse($result, 'Ledger integrity verified');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to verify ledger integrity');
        }
    }

    /**
     * Verify transfer integrity.
     *
     * @param int $transferId
     * @return JsonResponse
     */
    public function verifyTransfer(int $transferId): JsonResponse
    {
        try {
            $result = $this->ledgerService->verifyTransferIntegrity($transferId);

            if (!$result['valid']) {
                return $this->errorResponse(
                    'Transfer integrity check failed',
                    400,
                    $result
                );
            }

            return $this->successResponse($result, 'Transfer integrity verified');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to verify transfer integrity');
        }
    }

    /**
     * Get complete ledger audit trail.
     *
     * @param int $walletId
     * @return JsonResponse
     */
    public function auditTrail(int $walletId): JsonResponse
    {
        try {
            $trail = $this->ledgerService->getLedgerAuditTrail($walletId);

            return $this->successResponse($trail, 'Ledger audit trail retrieved');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve audit trail');
        }
    }
}

