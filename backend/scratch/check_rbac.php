<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\PagePermission;
use Illuminate\Http\Request;

$user = User::whereHas('roles', fn($q)=>$q->where('name', 'kepala-yayasan'))->first();

if (!$user) {
    echo "No kepala yayasan user found\n";
    
    // Create one if it doesn't exist to test
    $user = User::where('username', 'yayasan_test')->first();
    if (!$user) {
        $user = User::create([
            'username' => 'yayasan_test',
            'name' => 'Yayasan Test',
            'password' => bcrypt('password123'),
            'branch_id' => 1
        ]);
        $user->assignRole('kepala-yayasan');
        echo "Created test yayasan user.\n";
    }
} else {
    echo "Found user: {$user->username}\n";
}

echo "\nPermissions assigned to user:\n";
$perms = $user->getAllPermissions()->pluck('name')->toArray();
print_r($perms);

echo "\nController output for user-resources:\n";
$request = Request::create('/api/rbac/user-resources', 'GET');
$request->setUserResolver(fn() => $user);
$controller = app(\App\Http\Controllers\RbacController::class);
$response = $controller->userResources($request);
print_r($response->getData(true)['data']);

echo "\nController output for user-groups:\n";
$response2 = $controller->userGroups($request);
print_r($response2->getData(true)['data']);
