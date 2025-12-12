<?php

namespace App\Exceptions;

use Exception;

class TransferException extends Exception
{
    public static function senderWalletNotFound(int $walletId): self
    {
        return new self("Sender wallet not found", 404);
    }

    public static function receiverWalletNotFound(int $walletId): self
    {
        return new self("Receiver wallet not found", 404);
    }

    public static function sameWallet(): self
    {
        return new self("Sender and receiver wallets must be different", 400);
    }

    public static function insufficientBalance(float $balance, float $amount): self
    {
        return new self("Insufficient balance", 400);
    }

    public static function invalidAmount(): self
    {
        return new self("Invalid amount", 400);
    }

    public static function amountExceedsMaximum(float $maxAmount): self
    {
        return new self("Amount exceeds maximum allowed value", 400);
    }

    public static function receiverBalanceExceedsMaximum(float $maxBalance): self
    {
        return new self("Receiver balance would exceed maximum allowed value", 400);
    }
}

