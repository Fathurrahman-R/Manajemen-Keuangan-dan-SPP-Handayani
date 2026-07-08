<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(1); // Superadmin
\Illuminate\Support\Facades\Auth::login($user);

$requestWali = \Illuminate\Http\Request::create('/api/wali', 'GET', ['search' => 'a']);
$requestWali->headers->set('Accept', 'application/json');
$responseWali = app()->handle($requestWali);
echo "Status Wali: " . $responseWali->status() . "\n";
echo "Content Wali: " . $responseWali->getContent() . "\n";
