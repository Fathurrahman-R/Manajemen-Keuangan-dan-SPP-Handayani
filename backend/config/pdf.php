<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas kwitansi per unduhan gabungan
    |--------------------------------------------------------------------------
    |
    | DomPDF merender seluruh halaman di memori, jadi satu file berisi ribuan
    | kwitansi bisa menghabiskan memory_limit dan menggantung request. Kalau
    | filter aktif mencakup lebih dari batas ini, endpoint menolak dengan 422
    | dan meminta admin mempersempit filter.
    |
    */

    'kwitansi_bulk_limit' => (int) env('KWITANSI_BULK_LIMIT', 200),

];
