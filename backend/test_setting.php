<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$user = \App\Models\User::first();
$token = $user->createToken('test')->plainTextToken;

$response = Http::withToken($token)
    ->withHeaders([
        'Accept' => 'application/json',
        'X-Branch-Id' => '2' // Try changing to branch 2
    ])
    ->get('http://127.0.0.1:8080/api/setting');

dump($response->json());
