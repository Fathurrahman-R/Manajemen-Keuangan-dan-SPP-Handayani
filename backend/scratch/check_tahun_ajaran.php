<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(App\Models\TahunAjaran::all() as $t) {
    echo "$t->id | $t->nama | $t->tanggal_mulai - $t->tanggal_selesai | $t->status | branch:$t->branch_id\n";
}

echo "\n--- PengeluaranRequest tanggal_kebutuhan samples ---\n";
foreach(App\Models\PengeluaranRequest::take(10)->get() as $pr) {
    echo "ID:$pr->id | $pr->uraian | kebutuhan:$pr->tanggal_kebutuhan | status:$pr->status | branch:$pr->branch_id\n";
}
