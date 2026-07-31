<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class GenerateKodeTagihan
{
    /**
     * Nomor urut kode tagihan berikutnya untuk bulan berjalan.
     *
     * Implementasi lama memakai `LOCK TABLES` + `SET autocommit = 0`. Di
     * MySQL/MariaDB keduanya memicu implicit commit, sehingga transaksi yang
     * sedang membungkus pemanggilan ini langsung hilang — import tagihan
     * gagal dengan "There is no active transaction" dan baris yang sudah
     * sempat masuk tidak bisa di-rollback lagi.
     *
     * Gantinya memakai `lockForUpdate()` saat berada di dalam transaksi:
     * mengunci baris yang dibaca tanpa menyentuh transaksi pemanggil.
     * `kode_tagihan` sendiri adalah primary key, jadi tabrakan tetap
     * ditolak database sebagai lapisan terakhir.
     */
    public static function generate(): string
    {
        $prefix = 'TAG-'.now()->format('ym');

        $query = DB::table('tagihans')
            ->where('kode_tagihan', 'like', "$prefix-%")
            ->orderBy('kode_tagihan', 'desc');

        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $latest = $query->first();

        $increment = $latest
            ? intval(substr($latest->kode_tagihan, -4)) + 1
            : 1;

        return $prefix.'-'.str_pad((string) $increment, 4, '0', STR_PAD_LEFT);
    }
}
