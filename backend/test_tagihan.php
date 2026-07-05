<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jt = App\Models\JenisTagihan::create([
    'nama' => 'Tagihan Tes Besok',
    'jatuh_tempo' => '2026-07-04',
    'jumlah' => 150000,
    'branch_id' => 1,
    'tahun_ajaran_id' => 1
]);

App\Models\Tagihan::create([
    'kode_tagihan' => 'TAG-TEST-123',
    'jenis_tagihan_id' => $jt->id,
    'nis' => '000001',
    'tmp' => 150000,
    'status' => 'Belum Dibayar',
    'branch_id' => 1,
    'tahun_ajaran_id' => 1
]);

echo "Success\n";
