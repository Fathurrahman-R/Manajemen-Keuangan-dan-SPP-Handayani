<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(1); // Superadmin
\Illuminate\Support\Facades\Auth::login($user);

$request = \Illuminate\Http\Request::create('/api/ayah', 'GET', ['search' => 'a']);
$response = app()->handle($request);
echo "Status: " . $response->status() . "\n";
echo "Content: " . $response->getContent() . "\n";

$requestWali = \Illuminate\Http\Request::create('/api/wali', 'GET', ['search' => 'a']);
$responseWali = app()->handle($requestWali);
echo "Status Wali: " . $responseWali->status() . "\n";
echo "Content Wali: " . substr($responseWali->getContent(), 0, 500) . "\n";
