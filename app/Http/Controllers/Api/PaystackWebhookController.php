<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaystackService;
use App\Services\WalletTopupService;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function callback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference) {
            return response()->json([
                'status' => false,
                'message' => 'Missing payment reference.',
            ], 422);
        }

        $frontendUrl = config('services.paystack.frontend_callback_url');

        if ($frontendUrl) {
            $separator = str_contains($frontendUrl, '?') ? '&' : '?';
            return redirect()->away($frontendUrl.$separator.'reference='.urlencode($reference));
        }

        return response()->json([
            'status' => true,
            'message' => 'Callback received.',
            'reference' => $reference,
        ]);
    }

    public function webhook(Request $request, PaystackService $paystack, WalletTopupService $walletTopupService)
    {
        $secret = config('services.paystack.webhook_secret');
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        $computedSignature = hash_hmac('sha512', $payload, (string) $secret);

        if (! $signature || ! hash_equals($computedSignature, $signature)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        $event = $request->input('event');

        if ($event !== 'charge.success') {
            return response()->json([
                'status' => true,
                'message' => 'Event ignored.',
            ]);
        }

        $reference = (string) $request->input('data.reference');

        if ($reference === '') {
            return response()->json([
                'status' => false,
                'message' => 'Missing reference on event payload.',
            ], 422);
        }

        // Verify with Paystack before settlement.
        $verification = $paystack->verifyPayment($reference);

        if (! ($verification['status'] ?? false) || (($verification['data']['status'] ?? null) !== 'success')) {
            return response()->json([
                'status' => false,
                'message' => 'Could not verify successful payment.',
            ], 400);
        }

        $result = $walletTopupService->completeTopupByReference($reference);

        if (! $result['found']) {
            return response()->json([
                'status' => true,
                'message' => 'Reference not mapped to a local topup transaction.',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => $result['already_completed']
                ? 'Webhook received. Transaction already settled.'
                : 'Webhook received. Transaction settled successfully.',
            'reference' => $reference,
        ]);
    }
}
