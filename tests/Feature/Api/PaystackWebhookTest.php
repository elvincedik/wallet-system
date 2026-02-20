<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PaystackService;

test('paystack webhook rejects invalid signature', function () {
    config()->set('services.paystack.webhook_secret', 'webhook-secret');

    $payload = json_encode([
        'event' => 'charge.success',
        'data' => ['reference' => 'TXN_123'],
    ]);

    $this->withHeaders([
        'x-paystack-signature' => 'invalid-signature',
    ])->call(
        'POST',
        '/api/paystack/webhook',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        $payload
    )->assertStatus(401);
});

test('paystack webhook settles pending topup once', function () {
    config()->set('services.paystack.webhook_secret', 'webhook-secret');

    $user = User::factory()->create();
    $wallet = Wallet::create([
        'user_id' => $user->id,
        'balance' => 0,
    ]);

    Transaction::create([
        'wallet_id' => $wallet->id,
        'type' => 'topup',
        'amount' => 5000,
        'status' => 'pending',
        'reference' => 'TXN_SETTLE_1',
    ]);

    $this->mock(PaystackService::class, function ($mock) {
        $mock->shouldReceive('verifyPayment')
            ->once()
            ->andReturn([
                'status' => true,
                'data' => [
                    'status' => 'success',
                ],
            ]);
    });

    $payloadArray = [
        'event' => 'charge.success',
        'data' => ['reference' => 'TXN_SETTLE_1'],
    ];
    $payload = json_encode($payloadArray);
    $signature = hash_hmac('sha512', $payload, 'webhook-secret');

    $this->withHeaders([
        'x-paystack-signature' => $signature,
    ])->call(
        'POST',
        '/api/paystack/webhook',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        $payload
    )->assertOk();

    $this->assertDatabaseHas('transactions', [
        'reference' => 'TXN_SETTLE_1',
        'status' => 'completed',
    ]);

    $wallet->refresh();
    expect((float) $wallet->balance)->toBe(5000.0);
});

test('callback endpoint accepts reference', function () {
    $this->getJson('/api/paystack/callback?reference=TXN_CB_1')
        ->assertOk()
        ->assertJson([
            'status' => true,
            'reference' => 'TXN_CB_1',
        ]);
});
