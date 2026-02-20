<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    private function fromKobo(int $amount): float
    {
        return $amount / 100;
    }

    /**
     * Get user's wallet with balance
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Create wallet if not exists
        $wallet = $user->wallet()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        return response()->json([
            'id' => $wallet->id,
            'balance' => $this->fromKobo((int) $wallet->balance),
            'currency' => 'NGN'
        ]);
    }

    /**
     * Get wallet transactions
     */
    public function transactions(Request $request)
    {
        $user = $request->user();

        // Create or get wallet
        $wallet = $user->wallet()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $transactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => $this->fromKobo((int) $t->amount),
                'status' => $t->status,
                'reference' => $t->reference,
                'created_at' => $t->created_at->toIso8601String()
            ]);

        return response()->json(['transactions' => $transactions]);
    }
}
