<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API token for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::first();

        if (!$user) {
            $this->error('No user found. Please run: php artisan db:seed');
            return;
        }

        $token = $user->createToken('api-test')->plainTextToken;

        $this->info("\n========================================");
        $this->info("API Token Generated Successfully!");
        $this->info("========================================");
        $this->line("Email: " . $user->email);
        $this->line("\nAPI Token:\n");
        $this->line($token);
        $this->info("\n========================================");
        $this->info("Use this in Postman header:");
        $this->info("Authorization: Bearer " . $token);
        $this->info("========================================\n");
    }
}
