<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaystackWebhookController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/paystack/callback', [PaystackWebhookController::class, 'callback']);
Route::post('/paystack/webhook', [PaystackWebhookController::class, 'webhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Wallet routes
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);

    // Payment routes
    Route::post('/topup/initiate', [PaymentController::class, 'initiateTopup']);
    Route::post('/topup/verify', [PaymentController::class, 'verifyPayment']);
});
