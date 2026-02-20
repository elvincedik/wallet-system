<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('UPDATE wallets SET balance = ROUND(balance * 100)');
            DB::statement('UPDATE transactions SET amount = ROUND(amount * 100)');
            DB::statement('ALTER TABLE wallets MODIFY balance BIGINT UNSIGNED NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE transactions MODIFY amount BIGINT UNSIGNED NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE wallets MODIFY balance DECIMAL(15,2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE transactions MODIFY amount DECIMAL(15,2) NOT NULL');
            DB::statement('UPDATE wallets SET balance = balance / 100');
            DB::statement('UPDATE transactions SET amount = amount / 100');
        }
    }
};
