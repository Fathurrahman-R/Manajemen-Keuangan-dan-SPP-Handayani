<?php

namespace App\Services;

use App\Models\Tagihan;

class GenerateKodeTagihan
{
    /**
     * Create a new class instance.
     */
    public static function generate()
    {
        $year = now()->format('y'); // 25
        $month = now()->format('m'); // 01
        $prefix = "TAG-$year$month"; // TAG-2501

        // cek kode terakhir
        $latest = Tagihan::where('kode_tagihan', 'like', "$prefix-%")
            ->orderBy('kode_tagihan', 'desc')
            ->first();

        if (!$latest) {
            $increment = 1;
        } else {
            // ambil increment terakhir
            $lastNumber = intval(substr($latest->kode_tagihan, -4));
            $increment = $lastNumber + 1;
        }

        // format menjadi 4 digit
        $increment = str_pad($increment, 4, '0', STR_PAD_LEFT);

        return "$prefix-$increment";  // hasil akhir
    }
}
