<?php

namespace App\Jobs;

use App\Services\PaystackService;
use App\Services\WalletTopupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

class ProcessPaystackPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $reference;

    public ?int $tries = 3;

    public function __construct(string $reference)
    {
        $this->reference = $reference;
    }

    public function handle(PaystackService $paystack, WalletTopupService $walletTopupService, LoggerInterface $logger)
    {
        // Verify with Paystack before settlement.
        $verification = $paystack->verifyPayment($this->reference);

        if (! ($verification['status'] ?? false) || (($verification['data']['status'] ?? null) !== 'success')) {
            $logger->warning('Paystack verification failed for reference: '.$this->reference);
            return;
        }

        $result = $walletTopupService->completeTopupByReference($this->reference);

        if (! $result['found']) {
            $logger->info('Paystack webhook reference not mapped: '.$this->reference);
            return;
        }

        if ($result['already_completed']) {
            $logger->info('Transaction already completed: '.$this->reference);
            return;
        }

        $logger->info('Transaction settled: '.$this->reference);
    }

    public function failed(\Throwable $exception)
    {
        // Optionally notify or log failures for operator attention
        // Laravel will record failed jobs to the failed_jobs table if configured
    }
}
