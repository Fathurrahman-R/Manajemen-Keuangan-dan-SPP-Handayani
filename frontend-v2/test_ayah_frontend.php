<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fake login
$user = \App\Models\User::find(1);
\Illuminate\Support\Facades\Auth::login($user);

// Simulate ApiService client (it uses session token)
// Let's just create a token for user 1
$token = $user->createToken('test')->plainTextToken;
session(['data' => ['token' => $token]]);

$response = \App\Services\ApiService::client()->get('/ayah', ['search' => 'a']);
echo "Status: " . $response->status() . "\n";
print_r($response->json());
