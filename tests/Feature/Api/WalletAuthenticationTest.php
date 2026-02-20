<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PaystackService;
use Laravel\Sanctum\Sanctum;

test('wallet and topup endpoints require authentication', function () {
    $this->getJson('/api/wallet')->assertUnauthorized();
    $this->getJson('/api/wallet/transactions')->assertUnauthorized();
    $this->postJson('/api/topup/initiate', ['amount' => 1000])->assertUnauthorized();
    $this->postJson('/api/topup/verify', ['reference' => 'TXN_TEST'])->assertUnauthorized();
});

test('authenticated user sees their own wallet', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Wallet::create([
        'user_id' => $firstUser->id,
        'balance' => 1000,
    ]);

    Wallet::create([
        'user_id' => $secondUser->id,
        'balance' => 2500,
    ]);

    Sanctum::actingAs($secondUser);

    $this->getJson('/api/wallet')
        ->assertOk()
        ->assertJson([
            'balance' => 2500.0,
        ]);
});

test('authenticated user can initiate topup for own wallet', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->mock(PaystackService::class, function ($mock) {
        $mock->shouldReceive('initializePayment')
            ->once()
            ->andReturn([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://paystack.test/checkout',
                    'access_code' => 'access-code',
                ],
            ]);
    });

    $this->postJson('/api/topup/initiate', ['amount' => 5000])
        ->assertOk()
        ->assertJsonPath('status', true);

    $wallet = Wallet::where('user_id', $user->id)->first();

    expect($wallet)->not->toBeNull();
    $this->assertDatabaseHas('transactions', [
        'wallet_id' => $wallet->id,
        'type' => 'topup',
        'amount' => '5000.00',
        'status' => 'pending',
    ]);
});

test('user can not verify another users topup reference', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $wallet = Wallet::create([
        'user_id' => $owner->id,
        'balance' => 0,
    ]);

    $transaction = Transaction::create([
        'wallet_id' => $wallet->id,
        'type' => 'topup',
        'amount' => 5000,
        'status' => 'pending',
        'reference' => 'TXN_OWNER_ONLY',
    ]);

    Sanctum::actingAs($otherUser);

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

    $this->postJson('/api/topup/verify', ['reference' => $transaction->reference])
        ->assertNotFound();
});
