<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;

class GenerateKodePembayaran
{
    /**
     * Create a new class instance.
     */
    public static function generate()
    {
        $year = now()->format('y');
        $month = now()->format('m');
        $prefix = "PAY-$year$month";

        // LOCK tabel (harus diluar transaction)
        DB::statement("SET autocommit = 0;");
        DB::statement("LOCK TABLES pembayarans WRITE;");

        $latest = DB::table('pembayarans')
            ->where('kode_pembayaran', 'like', "$prefix-%")
            ->orderBy('kode_pembayaran', 'desc')
            ->first();

        if (!$latest) {
            $increment = 1;
        } else {
            $lastNumber = intval(substr($latest->kode_pembayaran, -4));
            $increment = $lastNumber + 1;
        }

        $increment = str_pad($increment, 4, '0', STR_PAD_LEFT);

        $kode = "$prefix-$increment";

        // UNLOCK dan enable autocommit kembali
        DB::statement("UNLOCK TABLES;");
        DB::statement("SET autocommit = 1;");

        return $kode;
    }

}
