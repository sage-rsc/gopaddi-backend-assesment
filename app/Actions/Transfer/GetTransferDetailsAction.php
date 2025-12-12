<?php

namespace App\Actions\Transfer;

use App\Actions\Action;
use App\Models\Transfer;
use App\Repositories\TransferRepository;

class GetTransferDetailsAction extends Action
{
    public function __construct(
        private readonly TransferRepository $transferRepository
    ) {}

    /**
     * Execute getting transfer details.
     *
     * @param int $transferId
     * @return Transfer|null
     */
    public function execute(...$arguments): ?Transfer
    {
        if (empty($arguments) || !is_int($arguments[0])) {
            throw new \InvalidArgumentException('Transfer ID must be provided as an integer');
        }
        
        $transferId = (int) $arguments[0];
        
        return $this->transferRepository->findById($transferId);
    }
}

