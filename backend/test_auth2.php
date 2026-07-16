<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/api/test-auth', function(Request $request) {
    return response()->json([
        'request_user_branch' => $request->user()->branch_id,
        'auth_user_branch' => Auth::user()->branch_id,
        'same_instance' => $request->user() === Auth::user()
    ]);
})->middleware(['auth:sanctum', \App\Http\Middleware\ActiveBranchContextMiddleware::class]);

$user = \App\Models\User::first();
$token = $user->createToken('test')->plainTextToken;

$request = Request::create('/api/test-auth', 'GET');
$request->headers->set('Authorization', 'Bearer '.$token);
$request->headers->set('X-Branch-Id', '2');

$response = $kernel->handle($request);
dump(json_decode($response->getContent(), true));
