<?php

use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckBearerToken;



Route::middleware([CheckBearerToken::class])->group(function () {

    // Wallet routes
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);

    // Payment routes
    Route::post('/topup/initiate', [PaymentController::class, 'initiateTopup']);
    Route::post('/topup/verify', [PaymentController::class, 'verifyPayment']);

    // User info
    Route::get('/user', function (Request $request) {
        return \App\Models\User::first();
    });

});
