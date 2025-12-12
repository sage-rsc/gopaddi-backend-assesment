<?php

namespace App\Services;

use App\Actions\Transfer\GetTransferDetailsAction;
use App\Actions\Transfer\GetWalletTransfersAction;
use App\Actions\Transfer\InitiateTransferAction;
use App\Models\Transfer;

class TransferService
{
    public function __construct(
        private InitiateTransferAction $initiateTransferAction,
        private GetTransferDetailsAction $getTransferDetailsAction,
        private GetWalletTransfersAction $getWalletTransfersAction
    ) {}

    /**
     * Initiate transfer.
     *
     * @param int $senderWalletId
     * @param int $receiverWalletId
     * @param float $amount
     * @param string|null $description
     * @return Transfer
     */
    public function initiateTransfer(
        int $senderWalletId,
        int $receiverWalletId,
        float $amount,
        ?string $description = null
    ): Transfer {
        return $this->initiateTransferAction->handle(
            $senderWalletId,
            $receiverWalletId,
            $amount,
            $description
        );
    }

    /**
     * Get transfer details.
     *
     * @param int $transferId
     * @return Transfer|null
     */
    public function getTransferDetails(int $transferId): ?Transfer
    {
        return $this->getTransferDetailsAction->handle($transferId);
    }

    /**
     * Get wallet transfers.
     *
     * @param int $walletId
     * @return array
     */
    public function getWalletTransfers(int $walletId): array
    {
        return $this->getWalletTransfersAction->handle($walletId);
    }
}

