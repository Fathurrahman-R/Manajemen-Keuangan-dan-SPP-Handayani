<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$user = \App\Models\User::first();
$token = $user->createToken('test')->plainTextToken;

$response = Http::withToken($token)
    ->withHeaders(['Accept' => 'application/json'])
    ->get('http://127.0.0.1:8080/api/rbac/roles/permissions-tree');

$audiences = $response->json()['data'];

$normalised = [];
// This is exactly the logic in RoleManagement::getPermissionAudiences
if (isset($audiences['audiences'])) {
    $audiences = $audiences['audiences'];
} else {
    $audiences = [
        'default' => [
            'label' => 'Default Audience',
            'groups' => array_filter(
                $audiences,
                fn ($v, $k) => is_array($v) && $k !== 'audiences',
                ARRAY_FILTER_USE_BOTH
            ),
        ],
    ];
}

foreach ($audiences as $key => $audience) {
    $label = $audience['label'] ?? $key;
    $groups = $audience['groups'] ?? [];

    $normalisedGroups = [];
    foreach ($groups as $groupName => $permissions) {
        if (! is_array($permissions)) {
            continue;
        }
        $map = [];
        foreach ($permissions as $perm) {
            if (! isset($perm['name'])) {
                continue;
            }
            $map[$perm['name']] = $perm['label'] ?? $perm['name'];
        }
        if ($map !== []) {
            $normalisedGroups[$groupName] = $map;
        }
    }
    if ($normalisedGroups !== []) {
        $normalised[$key] = [
            'label' => $label,
            'groups' => $normalisedGroups,
        ];
    }
}

dump($normalised);
