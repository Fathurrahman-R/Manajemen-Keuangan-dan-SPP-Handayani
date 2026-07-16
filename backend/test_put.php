<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$user = \App\Models\User::first();
$token = $user->createToken('test')->plainTextToken;

$payload = [
    'tagihan_baru_enabled' => true,
    'reminder_enabled' => true,
    'kwitansi_enabled' => true,
    'overdue_enabled' => true,
    'reminder_days_before' => [7, 3, 1],
    'overdue_interval_days' => 7,
];

$response = Http::withToken($token)
    ->withHeaders(['Accept' => 'application/json'])
    ->put('http://127.0.0.1:8080/api/notification-settings', $payload);

dump($response->status(), $response->json());
