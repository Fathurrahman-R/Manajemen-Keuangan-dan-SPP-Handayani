<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

$controller = app(\App\Http\Controllers\KasController::class);

$request = Request::create('/api/laporan/kas', 'GET', ['bulan' => 7, 'tahun' => 2026]);
// Mock auth
$user = \App\Models\User::where('username', 'yayasan')->first();
$request->setUserResolver(fn() => $user);

try {
    $response = $controller->kasHarian($request);
    echo "kasHarian OK. Data count: " . count($response->toArray($request)) . "\n";
} catch (\Exception $e) {
    echo "kasHarian Error: " . $e->getMessage() . "\n";
}

$requestRekap = Request::create('/api/laporan/rekap', 'GET', ['tahun' => 2026]);
$requestRekap->setUserResolver(fn() => $user);
try {
    $response = $controller->rekapBulanan($requestRekap);
    echo "rekapBulanan OK. Data count: " . count($response->toArray($requestRekap)) . "\n";
} catch (\Exception $e) {
    echo "rekapBulanan Error: " . $e->getMessage() . "\n";
}
