<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$row = [
    'nis' => '25600',
    'nisn' => null,
    'nama' => 'Elvin Mahesa',
    'jenis_kelamin' => 'Laki - laki',
    'tempat_lahir' => 'Landak',
    'tanggal_lahir' => '2019-11-23',
    'agama' => 'Katolik',
    'alamat' => 'Jl. Dharma Putra Gg. 24',
    'jenjang' => 'KB',
    'kelas' => 'KB',
    'kategori' => 'Umum',
    'status' => 'Aktif',
    'tahun_diterima' => '2023',
    'nama_ayah' => 'Marsianus Dudung',
    'pekerjaan_ayah' => 'Petani',
    'nama_ibu' => '',
    'pekerjaan_ibu' => '',
    'nama_wali' => '',
    'pekerjaan_wali' => '',
    'no_hp_wali' => '081349051036',
    'alamat_wali' => 'Jl. Dharma Putra Gg. 24'
];

$validator = new \App\Imports\SiswaImportValidator();
$validator->collection(collect([collect($row)]));
$parsed = $validator->getRows();
echo "Parsed: " . json_encode($parsed) . "\n";

$service = app(\App\Services\ImportExport\SiswaImportService::class);
$branchId = 1;
$existingNis = [];
$kelasRecords = \App\Models\Kelas::where('branch_id', $branchId)->get()->keyBy(function ($k) { return strtolower($k->nama . '|' . $k->jenjang); });
$nisInFile = [];

try {
    $periodeAktif = \App\Models\TahunAjaran::getAktif($branchId);
    if (!$periodeAktif) {
        echo "No periode aktif\n";
    } else {
        $successCount = $service->processRows($parsed, $branchId, 'test_batch_123', $periodeAktif->id);
        echo "\nSuccess count: " . $successCount . "\n";
    }
} catch (\Exception $e) {
    echo "\nException during processRows:\n" . $e->getMessage() . "\n" . $e->getTraceAsString();
}
