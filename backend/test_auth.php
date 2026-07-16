<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = \App\Models\User::first();
$token = $user->createToken('test')->plainTextToken;

// Simulate request
$request = Request::create('/api/setting', 'GET');
$request->headers->set('Authorization', 'Bearer '.$token);

// Process through middleware
$response = $kernel->handle($request);
dump($response->getContent());
