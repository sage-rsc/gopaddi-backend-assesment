<?php

namespace App\Exceptions;

use Exception;

class WalletException extends Exception
{
    public static function walletNotFound(int $walletId): self
    {
        return new self("Wallet with ID {$walletId} not found", 404);
    }

    public static function userAlreadyHasWallet(int $userId): self
    {
        return new self("User with ID {$userId} already has a wallet", 400);
    }

    public static function insufficientBalance(float $balance, float $amount): self
    {
        return new self(
            "Insufficient balance. Available: {$balance}, Required: {$amount}",
            400
        );
    }

    public static function cannotDeleteNonZeroBalance(float $balance): self
    {
        return new self(
            "Cannot delete wallet with non-zero balance. Current balance: {$balance}",
            400
        );
    }

    public static function invalidAmount(): self
    {
        return new self("Amount must be greater than zero", 400);
    }

    public static function amountExceedsMaximum(float $maxAmount): self
    {
        return new self("Amount exceeds maximum allowed value of {$maxAmount}", 400);
    }

    public static function balanceExceedsMaximum(float $maxBalance): self
    {
        return new self("Balance would exceed maximum allowed value of {$maxBalance}", 400);
    }

    public static function userNotFound(int $userId): self
    {
        return new self("User with ID {$userId} not found", 404);
    }
}

