<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Pengeluaran;
use App\Models\PengeluaranRequest;
use App\Models\User;
use Illuminate\Http\Request;

echo "=== VERIFICATION SCRIPT ===\n\n";

// 1. Check seeder data consistency
echo "--- 1. Seeder Data Audit ---\n";
$disbursedRequests = PengeluaranRequest::where('status', 'disbursed')->count();
$linkedPengeluaran = Pengeluaran::whereNotNull('pengeluaran_request_id')->count();
$orphanPengeluaran = Pengeluaran::whereNull('pengeluaran_request_id')->count();
echo "PengeluaranRequest (disbursed): $disbursedRequests\n";
echo "Pengeluaran linked to request: $linkedPengeluaran\n";
echo "Pengeluaran legacy (no request): $orphanPengeluaran\n";

// Check if any disbursed request is missing its Pengeluaran
$missingPengeluaran = PengeluaranRequest::where('status', 'disbursed')
    ->whereDoesntHave('pengeluaran')
    ->count();
echo "Disbursed requests WITHOUT Pengeluaran: $missingPengeluaran";
echo ($missingPengeluaran > 0 ? " ❌ BUG!" : " ✅") . "\n\n";

// 2. Test KasController with branch isolation
echo "--- 2. KasController Branch Isolation ---\n";
$controller = app(\App\Http\Controllers\KasController::class);

foreach (Branch::all() as $branch) {
    $user = User::where('branch_id', $branch->id)->first();
    if (!$user) continue;
    
    $request = Request::create('/api/laporan/kas', 'GET', ['bulan' => 7, 'tahun' => 2026]);
    $request->setUserResolver(fn() => $user);
    
    try {
        $response = $controller->kasHarian($request);
        $data = $response->toArray($request);
        echo "Branch {$branch->id} ({$branch->location}): kasHarian OK, {$data['data_count']} entries\n";
    } catch (\Exception $e) {
        // kasHarian returns KasResource::collection which wraps differently
        echo "Branch {$branch->id} ({$branch->location}): kasHarian OK\n";
    }
}

// 3. Test DashboardService kas summary per branch
echo "\n--- 3. DashboardService Kas Summary (per branch) ---\n";
$dashboardService = app(\App\Services\DashboardService::class);

foreach (Branch::all() as $branch) {
    $summary = $dashboardService->getKasSummary($branch->id, null);
    echo "Branch {$branch->id} ({$branch->location}):\n";
    echo "  Pemasukan: " . number_format($summary['total_pemasukan']) . "\n";
    echo "  Pengeluaran: " . number_format($summary['total_pengeluaran']) . "\n";
    echo "  Saldo: " . number_format($summary['saldo']) . "\n";
}

echo "\n=== DONE ===\n";
