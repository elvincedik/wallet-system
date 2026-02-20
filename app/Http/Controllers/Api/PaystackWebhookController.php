<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaystackService;
use App\Services\WalletTopupService;
use App\Jobs\ProcessPaystackPayment;
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

        // Dispatch background job to verify and settle the payment.
        ProcessPaystackPayment::dispatch($reference)->onQueue('payments');

        return response()->json([
            'status' => true,
            'message' => 'Webhook received. Processing queued.',
            'reference' => $reference,
        ]);
    }
}
