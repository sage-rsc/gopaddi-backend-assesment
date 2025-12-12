<?php

namespace App\Exceptions;

use Exception;

class WalletException extends Exception
{
    public static function walletNotFound(int $walletId): self
    {
        return new self("Wallet not found", 404);
    }

    public static function userAlreadyHasWallet(int $userId): self
    {
        return new self("User already has a wallet", 400);
    }

    public static function insufficientBalance(float $balance, float $amount): self
    {
        return new self("Insufficient balance", 400);
    }

    public static function cannotDeleteNonZeroBalance(float $balance): self
    {
        return new self("Cannot delete wallet with non-zero balance", 400);
    }

    public static function invalidAmount(): self
    {
        return new self("Invalid amount", 400);
    }

    public static function amountExceedsMaximum(float $maxAmount): self
    {
        return new self("Amount exceeds maximum allowed value", 400);
    }

    public static function balanceExceedsMaximum(float $maxBalance): self
    {
        return new self("Balance would exceed maximum allowed value", 400);
    }

    public static function userNotFound(int $userId): self
    {
        return new self("User not found", 404);
    }
}

