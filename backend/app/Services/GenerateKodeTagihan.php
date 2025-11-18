<?php

namespace App\Services;

use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;

class GenerateKodeTagihan
{
    /**
     * Create a new class instance.
     */
    public static function generate()
    {
        $year = now()->format('y');
        $month = now()->format('m');
        $prefix = "TAG-$year$month";

        // LOCK tabel (harus diluar transaction)
        DB::statement("SET autocommit = 0;");
        DB::statement("LOCK TABLES tagihans WRITE;");

        $latest = DB::table('tagihans')
            ->where('kode_tagihan', 'like', "$prefix-%")
            ->orderBy('kode_tagihan', 'desc')
            ->first();

        if (!$latest) {
            $increment = 1;
        } else {
            $lastNumber = intval(substr($latest->kode_tagihan, -4));
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
