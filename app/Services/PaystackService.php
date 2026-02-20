<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaystackService
{
    private $paystackBaseUrl = 'https://api.paystack.co';
    private $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
    }

    /**
     * Initialize a payment transaction
     */
    public function initializePayment($email, $amount, $reference)
    {
        $payload = [
            'email' => $email,
            'amount' => $amount * 100, // Paystack uses kobo
            'reference' => $reference,
        ];

        $callbackUrl = config('services.paystack.callback_url');

        if (! empty($callbackUrl)) {
            $payload['callback_url'] = $callbackUrl;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json'
        ])->post("{$this->paystackBaseUrl}/transaction/initialize", $payload);

        return $response->json();
    }

    /**
     * Verify a payment transaction
     */
    public function verifyPayment($reference)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->get("{$this->paystackBaseUrl}/transaction/verify/{$reference}");

        return $response->json();
    }
}
