<?php

require __DIR__ . '/bootstrap/app.php';

$app = app();

// Create a test user
$user = \App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password123'),
    'email_verified_at' => now(),
]);

// Create API token
$token = $user->createToken('api-test')->plainTextToken;

echo "\n========================================\n";
echo "User Created Successfully!\n";
echo "========================================\n";
echo "Email: test@example.com\n";
echo "Password: password123\n\n";
echo "API Token:\n";
echo $token . "\n";
echo "========================================\n";
echo "\nUse this token in Postman headers:\n";
echo "Authorization: Bearer " . $token . "\n";
echo "========================================\n\n";
