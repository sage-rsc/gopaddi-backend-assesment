<?php

namespace App\Http\Controllers;

use App\Exceptions\WalletException;
use App\Http\Requests\CreateWalletRequest;
use App\Http\Requests\FundWalletRequest;
use App\Http\Requests\WithdrawWalletRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;

class WalletController extends BaseController
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function create(CreateWalletRequest $request): JsonResponse
    {
        try {
            $wallet = $this->walletService->createWallet($request->user_id);

            return $this->successResponse([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'balance' => round((float) $wallet->balance, 2),
                'created_at' => $wallet->created_at,
            ], 'Wallet created successfully', 201);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to create wallet');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $details = $this->walletService->getWalletDetails($id);

            if (!$details) {
                throw WalletException::walletNotFound($id);
            }

            return $this->successResponse([
                'wallet_id' => $details['wallet']->id,
                'user_id' => $details['wallet']->user_id,
                'balance' => $details['balance'],
                'transaction_summary' => $details['transaction_summary'],
            ], 'Wallet details retrieved successfully');
        } catch (WalletException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve wallet details');
        }
    }

    public function fund(FundWalletRequest $request, int $id): JsonResponse
    {
        try {
            $transaction = $this->walletService->fundWallet(
                $id,
                $request->amount,
                $request->description
            );

            return $this->successResponse([
                'transaction_id' => $transaction->id,
                'reference' => $transaction->reference,
                'amount' => round((float) $transaction->amount, 2),
                'type' => $transaction->type,
                'status' => $transaction->status,
            ], 'Wallet funded successfully');
        } catch (WalletException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to fund wallet');
        }
    }

    public function withdraw(WithdrawWalletRequest $request, int $id): JsonResponse
    {
        try {
            $transaction = $this->walletService->withdrawFromWallet(
                $id,
                $request->amount,
                $request->description
            );

            return $this->successResponse([
                'transaction_id' => $transaction->id,
                'reference' => $transaction->reference,
                'amount' => round((float) $transaction->amount, 2),
                'type' => $transaction->type,
                'status' => $transaction->status,
            ], 'Withdrawal successful');
        } catch (WalletException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to withdraw from wallet');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->walletService->deleteWallet($id);

            return $this->successResponse(null, 'Wallet deleted successfully');
        } catch (WalletException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to delete wallet');
        }
    }
}
