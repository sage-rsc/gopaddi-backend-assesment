<?php

namespace App\Http\Controllers;

use App\Exceptions\TransferException;
use App\Http\Requests\InitiateTransferRequest;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;

class TransferController extends BaseController
{
    public function __construct(
        private TransferService $transferService
    ) {}

    public function initiate(InitiateTransferRequest $request): JsonResponse
    {
        try {
            $transfer = $this->transferService->initiateTransfer(
                $request->sender_wallet_id,
                $request->receiver_wallet_id,
                $request->amount,
                $request->description
            );

            return $this->successResponse([
                'transfer_id' => $transfer->id,
                'reference' => $transfer->reference,
                'sender_wallet_id' => $transfer->sender_wallet_id,
                'receiver_wallet_id' => $transfer->receiver_wallet_id,
                'amount' => round((float) $transfer->amount, 2),
                'status' => $transfer->status,
                'created_at' => $transfer->created_at,
            ], 'Transfer initiated successfully', 201);
        } catch (TransferException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to initiate transfer');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $transfer = $this->transferService->getTransferDetails($id);

            if (!$transfer) {
                return $this->errorResponse('Transfer not found', 404);
            }

            return $this->successResponse([
                'transfer_id' => $transfer->id,
                'reference' => $transfer->reference,
                'sender' => [
                    'wallet_id' => $transfer->sender_wallet_id,
                    'user_id' => $transfer->senderWallet->user_id,
                    'user_name' => $transfer->senderWallet->user->name,
                ],
                'receiver' => [
                    'wallet_id' => $transfer->receiver_wallet_id,
                    'user_id' => $transfer->receiverWallet->user_id,
                    'user_name' => $transfer->receiverWallet->user->name,
                ],
                'amount' => round((float) $transfer->amount, 2),
                'description' => $transfer->description,
                'status' => $transfer->status,
                'created_at' => $transfer->created_at,
                'updated_at' => $transfer->updated_at,
            ], 'Transfer details retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve transfer details');
        }
    }

    public function walletTransfers(int $walletId): JsonResponse
    {
        try {
            $transfers = $this->transferService->getWalletTransfers($walletId);

            return $this->successResponse($transfers, 'Wallet transfers retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve wallet transfers');
        }
    }
}
