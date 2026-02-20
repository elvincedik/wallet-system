<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class WalletTopupService
{
    /**
     * Complete a topup transaction and credit wallet once.
     *
     * @return array{found: bool, already_completed: bool, transaction: ?\App\Models\Transaction, wallet_balance: ?float}
     */
    public function completeTopupByReference(string $reference, ?int $userId = null): array
    {
        return DB::transaction(function () use ($reference, $userId) {
            $query = Transaction::query()
                ->where('reference', $reference)
                ->where('type', 'topup')
                ->with('wallet');

            if ($userId !== null) {
                $query->whereHas('wallet', fn ($walletQuery) => $walletQuery->where('user_id', $userId));
            }

            /** @var \App\Models\Transaction|null $transaction */
            $transaction = $query->lockForUpdate()->first();

            if (! $transaction || ! $transaction->wallet) {
                return [
                    'found' => false,
                    'already_completed' => false,
                    'transaction' => null,
                    'wallet_balance' => null,
                ];
            }

            $wallet = $transaction->wallet()->lockForUpdate()->first();

            if (! $wallet) {
                return [
                    'found' => false,
                    'already_completed' => false,
                    'transaction' => null,
                    'wallet_balance' => null,
                ];
            }

            if ($transaction->status === 'completed') {
                return [
                    'found' => true,
                    'already_completed' => true,
                    'transaction' => $transaction,
                    'wallet_balance' => (float) $wallet->balance,
                ];
            }

            $transaction->status = 'completed';
            $transaction->save();

            $wallet->increment('balance', $transaction->amount);
            $wallet->refresh();

            return [
                'found' => true,
                'already_completed' => false,
                'transaction' => $transaction->fresh(),
                'wallet_balance' => (float) $wallet->balance,
            ];
        });
    }
}
