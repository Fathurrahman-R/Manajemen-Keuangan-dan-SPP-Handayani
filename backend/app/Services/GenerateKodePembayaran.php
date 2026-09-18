<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateKodePembayaran
{
    /**
     * @param  string|null  $tanggal  Tanggal pembayaran (Y-m-d). Diisi saat mencatat
     *                                pembayaran mundur agar prefix kode mengikuti
     *                                bulan transaksi, bukan bulan input.
     */
    public static function generate(?string $tanggal = null)
    {
        return static::generateMany(1, $tanggal)[0];
    }

    /**
     * Buat beberapa kode berurutan dalam sekali lock.
     *
     * WAJIB dipanggil di luar DB::transaction(): LOCK TABLES memicu implicit
     * commit di MySQL, sehingga transaksi yang sedang berjalan putus dan COMMIT
     * di ujungnya gagal dengan "There is no active transaction" — padahal baris
     * yang sudah di-insert terlanjur permanen.
     *
     * @return list<string>
     */
    public static function generateMany(int $jumlah, ?string $tanggal = null): array
    {
        if ($jumlah < 1) {
            return [];
        }

        $waktu = $tanggal ? Carbon::parse($tanggal) : now();
        $year = $waktu->format('y');
        $month = $waktu->format('m');
        $prefix = "PAY-$year$month";

        // LOCK tabel (harus diluar transaction)
        DB::statement('SET autocommit = 0;');
        DB::statement('LOCK TABLES pembayarans WRITE;');

        $latest = DB::table('pembayarans')
            ->where('kode_pembayaran', 'like', "$prefix-%")
            ->orderBy('kode_pembayaran', 'desc')
            ->first();

        if (! $latest) {
            $increment = 1;
        } else {
            $lastNumber = intval(substr($latest->kode_pembayaran, -4));
            $increment = $lastNumber + 1;
        }

        // UNLOCK dan enable autocommit kembali
        DB::statement('UNLOCK TABLES;');
        DB::statement('SET autocommit = 1;');

        $kode = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $kode[] = $prefix.'-'.str_pad((string) ($increment + $i), 4, '0', STR_PAD_LEFT);
        }

        return $kode;
    }
}
