<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $paystack;

    public function __construct(PaystackService $paystack)
    {
        $this->paystack = $paystack;
    }

    /**
     * Initiate a topup payment
     */
    public function initiateTopup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100'
        ]);

        $user = User::first(); // Get the authenticated user
        // Create or get wallet
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // Create transaction record
        $reference = 'TXN_' . Str::uuid();
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'topup',
            'amount' => $request->amount,
            'status' => 'pending',
            'reference' => $reference
        ]);

        // Initialize payment with Paystack
        $response = $this->paystack->initializePayment(
            $user->email,
            $request->amount,
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
                'amount' => $request->amount
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

        // Find transaction
        $transaction = Transaction::where('reference', $reference)->first();

        if (!$transaction) {
            return response()->json([
                'status' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        // Update transaction status
        $transaction->update(['status' => 'completed']);

        // Credit wallet
        $wallet = $transaction->wallet;
        $wallet->increment('balance', $transaction->amount);

        return response()->json([
            'status' => true,
            'message' => 'Payment verified and wallet credited',
            'data' => [
                'amount' => (float) $transaction->amount,
                'wallet_balance' => (float) $wallet->balance,
                'reference' => $reference
            ]
        ]);
    }
}
