<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\PaystackService;
use App\Services\WalletTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $paystack;
    protected $walletTopupService;

    public function __construct(PaystackService $paystack, WalletTopupService $walletTopupService)
    {
        $this->paystack = $paystack;
        $this->walletTopupService = $walletTopupService;
    }

    private function toKobo(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function fromKobo(int $amount): float
    {
        return $amount / 100;
    }

    /**
     * Initiate a topup payment
     */
    public function initiateTopup(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100'
        ]);
        $amountInKobo = $this->toKobo((float) $validated['amount']);

        $user = $request->user();

        // Create or get wallet
        $wallet = $user->wallet()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // Create transaction record
        $reference = 'TXN_' . Str::uuid();
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'topup',
            'amount' => $amountInKobo,
            'status' => 'pending',
            'reference' => $reference
        ]);

        // Initialize payment with Paystack
        $response = $this->paystack->initializePayment(
            $user->email,
            $amountInKobo,
            $reference
        );

        if (!$response['status']) {
            $transaction->update(['status' => 'failed']);
            return response()->json(['message' => 'Failed to initialize payment'], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment initialized',
            'data' => [
                'authorization_url' => $response['data']['authorization_url'],
                'access_code' => $response['data']['access_code'],
                'reference' => $reference,
                'amount' => $this->fromKobo($amountInKobo)
            ]
        ]);
    }

    /**
     * Verify payment and credit wallet
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'reference' => 'required|string'
        ]);

        $reference = $request->reference;
        $user = $request->user();

        // Verify with Paystack
        $response = $this->paystack->verifyPayment($reference);

        if (!$response['status']) {
            return response()->json([
                'status' => false,
                'message' => 'Payment verification failed'
            ], 400);
        }

        $paymentData = $response['data'];

        // Check if payment is successful
        if ($paymentData['status'] !== 'success') {
            return response()->json([
                'status' => false,
                'message' => 'Payment not successful',
                'payment_status' => $paymentData['status']
            ], 400);
        }

        $result = $this->walletTopupService->completeTopupByReference($reference, $user->id);

        if (! $result['found']) {
            return response()->json([
                'status' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        /** @var \App\Models\Transaction $transaction */
        $transaction = $result['transaction'];

        if ($result['already_completed']) {
            return response()->json([
                'status' => true,
                'message' => 'Payment already verified',
                'data' => [
                    'amount' => $this->fromKobo((int) $transaction->amount),
                    'wallet_balance' => $this->fromKobo((int) $result['wallet_balance']),
                    'reference' => $reference,
                ],
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment verified and wallet credited',
            'data' => [
                'amount' => $this->fromKobo((int) $transaction->amount),
                'wallet_balance' => $this->fromKobo((int) $result['wallet_balance']),
                'reference' => $reference
            ]
        ]);
    }
}
