<?php
/**
 * Audit Sinkronisasi Resource Key — v3
 * 
 * Membandingkan:
 * 1. PermissionResourceSeeder (page_permissions) ← frontend hasResource()
 * 2. PermissionEndpointSeeder (permission_endpoints) ← backend endpoint middleware
 */
function getResourceKeysFromArraySeeder($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    $keys = [];
    
    // Coba format associative: 'key' => 'value'
    preg_match_all("/'key'\s*=>\s*'([^']+)'/", $content, $m1);
    $keys = array_merge($keys, $m1[1]);
    
    // Coba format positional: [$resourceKey, ...
    preg_match_all("/\[\s*\n?\s*'([a-z][a-z0-9_.-]*)'/", $content, $m2);
    $keys = array_merge($keys, $m2[1]);
    
    return array_unique($keys);
}

function getEndpointMiddlewareKeys($routeFile) {
    if (!file_exists($routeFile)) return [];
    $content = file_get_contents($routeFile);
    preg_match_all("/endpoint\.permission:([a-z0-9_.-]+)/", $content, $m);
    return array_unique($m[1]);
}

function getFrontendResourceKeys($dirs) {
    $keys = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($files as $f) {
            if (!$f->isFile() || !in_array($f->getExtension(), ['php', 'blade.php'])) continue;
            $c = file_get_contents($f->getPathname());
            preg_match_all("/hasResource\s*\(\s*'([^']+)'\s*\)/", $c, $m);
            $keys = array_merge($keys, $m[1]);
        }
    }
    return array_unique($keys);
}

// ─── 1. Page Permission Resource Keys (PermissionResourceSeeder) ───
$pageSeederFile = __DIR__ . '/../backend/database/seeders/PermissionResourceSeeder.php';
$pageSeedKeys = getResourceKeysFromArraySeeder($pageSeederFile);
sort($pageSeedKeys);

// ─── 2. Endpoint Permission Resource Keys (PermissionEndpointSeeder) ───
$endpointSeederFile = __DIR__ . '/../backend/database/seeders/PermissionEndpointSeeder.php';
$endpointSeedKeys = getResourceKeysFromArraySeeder($endpointSeederFile);
sort($endpointSeedKeys);

// ─── 3. Frontend hasResource() ───
$frontendDirs = [
    __DIR__ . '/app/Helpers',
    __DIR__ . '/app/Filament/Pages',
    __DIR__ . '/app/Filament/Portal',
    __DIR__ . '/app/Livewire',
    __DIR__ . '/resources/views',
];
$frontendKeys = getFrontendResourceKeys($frontendDirs);
sort($frontendKeys);

// ─── 4. Endpoint middleware ───
$routeFile = __DIR__ . '/../backend/routes/api.php';
$endpointMiddlewareKeys = getEndpointMiddlewareKeys($routeFile);
sort($endpointMiddlewareKeys);

// ─── 5. RbacController (backend) used keys ───
$rbacFile = __DIR__ . '/../backend/app/Http/Controllers/RbacController.php';
$rbacContent = file_get_contents($rbacFile);
preg_match_all("/'([a-z][a-z0-9_.-]+)'/", $rbacContent, $rbacMatches);

// Helper to print
function printSection($title, $arr) {
    echo "$title (" . count($arr) . "):\n";
    foreach ($arr as $k) echo "  $k\n";
}

echo "============================================================\n";
echo "  RESOURCE KEY SINKRONISASI AUDIT - FINAL\n";
echo "============================================================\n\n";

printSection("PermissionResourceSeeder (page_permissions)", $pageSeedKeys);
echo "\n";
printSection("PermissionEndpointSeeder (permission_endpoints)", $endpointSeedKeys);
echo "\n";
printSection("Frontend hasResource()", $frontendKeys);
echo "\n";
printSection("Endpoint middleware", $endpointMiddlewareKeys);

echo "\n============================================================\n";
echo "  PERBANDINGAN\n";
echo "============================================================\n\n";

// ─── A. Frontend vs Page Seeder ───
echo "--- A. Frontend hasResource() vs PermissionResourceSeeder ---\n";
$missing = array_diff($frontendKeys, $pageSeedKeys);
if ($missing) {
    echo "❌ MISSING dari seeder:\n";
    foreach ($missing as $k) {
        // Cari apakah ada di kode dengan format berbeda
        $possibles = [];
        $n = str_replace('-', '.', $k);
        $n2 = str_replace('.', '-', $k);
        if (in_array($n, $pageSeedKeys)) $possibles[] = "coba '$n'";
        if (in_array($n2, $pageSeedKeys)) $possibles[] = "coba '$n2'";
        // Cek partial match
        foreach ($pageSeedKeys as $sk) {
            if (str_contains($sk, explode('.', $k)[0] ?? '')) {
                $possibles[] = "mirip '$sk'";
                break;
            }
        }
        $hint = $possibles ? " → " . implode(', ', $possibles) : '';
        echo "  - '$k'$hint\n";
    }
} else {
    echo "✅ ALL GOOD!\n";
}

// ─── B. Endpoint middleware vs Endpoint Seeder ───
echo "\n--- B. Endpoint middleware vs PermissionEndpointSeeder ---\n";
$missingEp = array_diff($endpointMiddlewareKeys, $endpointSeedKeys);
if ($missingEp) {
    echo "❌ MISSING dari endpoint seeder:\n";
    foreach ($missingEp as $k) echo "  - '$k'\n";
} else {
    echo "✅ ALL GOOD!\n";
}

// ─── C. Endpoint seeder vs Page seeder ───
echo "\n--- C. PermissionEndpointSeeder vs PermissionResourceSeeder ---\n";
$epVsPage = array_diff($endpointSeedKeys, $pageSeedKeys);
if ($epVsPage) {
    echo "⚠️  Endpoint keys ada di endpoint seeder tapi tidak di page seeder:\n";
    foreach ($epVsPage as $k) echo "  - '$k' — frontend hasResource() untuk key ini TIDAK akan pernah true!\n";
} else {
    echo "✅ ALL GOOD — semua endpoint seeder keys ada di page seeder!\n";
}

echo "\n============================================================\n";
echo "  DETAIL FRONTEND vs PAGE SEEDER\n";
echo "============================================================\n\n";
foreach ($frontendKeys as $k) {
    $found = in_array($k, $pageSeedKeys) ? '✅' : '❌';
    echo "  $found $k\n";
}

echo "\n============================================================\n";
echo "  DETAIL ENDPOINT MIDDLEWARE vs ENDPOINT SEEDER\n";
echo "============================================================\n\n";
foreach ($endpointMiddlewareKeys as $k) {
    $found = in_array($k, $endpointSeedKeys) ? '✅' : '❌';
    echo "  $found $k\n";
}
