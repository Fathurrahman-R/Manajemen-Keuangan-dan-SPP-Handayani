<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\User;
use App\Models\Tagihan;
use Illuminate\Http\Request;

echo "Total branches: " . Branch::count() . "\n";
$yayasan = User::where('username', 'yayasan')->first();
echo "Yayasan default branch_id: " . $yayasan->branch_id . "\n";

$dashboardService = app(\App\Services\DashboardService::class);

$branches = Branch::all();
foreach ($branches as $branch) {
    echo "--- Branch ID {$branch->id} ({$branch->location}) ---\n";
    $summary = $dashboardService->getSummary($branch->id, null, true);
    echo "Total Tagihan: " . $summary['total_tagihan'] . "\n";
    echo "Total Terbayar: " . $summary['total_terbayar'] . "\n";
    echo "Siswa Aktif: " . $summary['jumlah_siswa_aktif'] . "\n";
    echo "\n";
}
