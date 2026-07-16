<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fake login
$response = \Illuminate\Support\Facades\Http::post(env('API_URL').'/login', [
    'identifier' => 'yayasan@handayani.com',
    'password' => '!handayani123'
]);

if (!$response->successful()) {
    echo "Login failed!\n";
    print_r($response->json());
    exit;
}

$data = $response->json()['data'];
session()->put('data.token', $data['token']);
session()->put('data.permissions', $data['permissions']);
session()->put('data.roles', $data['roles']);
session()->put('data.id', $data['id']);

echo "Logged in successfully. Token: " . substr($data['token'], 0, 10) . "...\n";

// Test ApiService directly
$r = \App\Services\ApiService::client()->get('/rbac/user-resources');
echo "Status: " . $r->status() . "\n";
echo "Body: " . $r->body() . "\n";

$resources = \App\Helpers\PermissionHelper::getUserResources();
echo "Resources count: " . count($resources) . "\n";
print_r($resources);

$groups = \App\Helpers\PermissionHelper::getUserGroups();
echo "Groups count: " . count($groups) . "\n";
print_r($groups);
