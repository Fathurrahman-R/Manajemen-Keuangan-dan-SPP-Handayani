<?php

$dir = __DIR__ . '/../app/Filament/Pages';
$files = glob($dir . '/*.php');

$mappings = [
    "'branch-approval-setting'" => "'auto-approve.view'",
    "'branch'" => "'branch.view'",
    "'kategori'" => "'kategori.view'",
    "'kelas'" => "'kelas.view'",
    "'siswa'" => "'siswa.view'",
    "'kenaikan-kelas'" => "'kenaikan-kelas.view'",
    "'kas-harian'" => "'laporan.kas'",
    "'rekap-bulanan'" => "'laporan.rekap'",
    "'akun-siswa'" => "'akun-siswa.view'",
    "'notification-logs'" => "'notification-logs.view'",
    "'notification-setting'" => "'notification-setting.view'",
    "'pengeluaran'" => "'pengeluaran.view'",
    "'user-management'" => "'users.view'",
    "'app-setting'" => "'pengaturan.view'",
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    foreach ($mappings as $old => $new) {
        // Only replace inside hasResource( ... ) calls to avoid false positives
        $search = "hasResource($old)";
        $replace = "hasResource($new)";
        if (str_contains($content, $search)) {
            $content = str_replace($search, $replace, $content);
            $changed = true;
        }
    }
    
    if ($changed) {
        file_put_contents($file, $content);
        echo "Updated " . basename($file) . "\n";
    }
}
