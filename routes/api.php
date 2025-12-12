<?php

use App\Http\Controllers\LedgerController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['token', 'rate.limit:60,1'])->group(function () {
    // Wallet Management APIs
    Route::post('/wallets', [WalletController::class, 'create'])->middleware('rate.limit:10,1');
    Route::get('/wallets/{id}', [WalletController::class, 'show']);
    Route::post('/wallets/{id}/fund', [WalletController::class, 'fund'])->middleware('rate.limit:30,1');
    Route::post('/wallets/{id}/withdraw', [WalletController::class, 'withdraw'])->middleware('rate.limit:30,1');
    Route::delete('/wallets/{id}', [WalletController::class, 'destroy'])->middleware('rate.limit:5,1');

    // Transfer APIs
    Route::post('/transfers', [TransferController::class, 'initiate'])->middleware('rate.limit:20,1');
    Route::get('/transfers/{id}', [TransferController::class, 'show']);
    Route::get('/wallets/{walletId}/transfers', [TransferController::class, 'walletTransfers']);

    // Ledger & Audit APIs
    Route::get('/ledger/wallets/{walletId}/verify', [LedgerController::class, 'verifyWallet']);
    Route::get('/ledger/transfers/{transferId}/verify', [LedgerController::class, 'verifyTransfer']);
    Route::get('/ledger/wallets/{walletId}/audit-trail', [LedgerController::class, 'auditTrail']);
});

