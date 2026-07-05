<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('username', '000001')->first();
echo 'User: ' . $user->name . PHP_EOL;
$tagihan = \App\Models\Tagihan::where('nis', $user->username)->where('status', '!=', 'Lunas')->first();
if (!$tagihan) {
    die("No unpaid tagihan found");
}
$sisa = $tagihan->jenis_tagihan->jumlah - $tagihan->tmp;
echo 'Tagihan: ' . $tagihan->kode_tagihan . ' Sisa: ' . $sisa . PHP_EOL;

try {
    $service = app(\App\Services\Midtrans\MidtransInitiationService::class);
    $result = $service->initiate($user, $tagihan->kode_tagihan, 10000, 'qris');
    echo 'Success: ' . $result->orderId . ' token: ' . $result->snapToken . PHP_EOL;
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
