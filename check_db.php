<?php
require __DIR__ . '/vendor/autoload.php';
$envPath = realpath(__DIR__ . '/.env');
echo ".env path: " . ($envPath ?: 'none') . PHP_EOL;
echo ".env mtime: " . (@filemtime(__DIR__ . '/.env') ?: 'n/a') . PHP_EOL;
echo ".env contents:\n" . @file_get_contents(__DIR__ . '/.env') . PHP_EOL;

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "database.default: " . config('database.default') . PHP_EOL;
echo "database.connection config: " . json_encode(config('database.connections.' . config('database.default'))) . PHP_EOL;

try {
	$db = Illuminate\Support\Facades\DB::select('select database() as db');
	echo "select database(): " . ($db[0]->db ?? json_encode($db)) . PHP_EOL;
} catch (Throwable $e) {
	echo "DB select failed: " . $e->getMessage() . PHP_EOL;
}
