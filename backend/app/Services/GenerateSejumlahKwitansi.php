<?php

namespace App\Services;

use App\Models\Pembayaran;

class GenerateSejumlahKwitansi
{
    /**
     * Konversi angka ke bentuk terbilang Indonesia.
     */
    public static function toWords(int|float|string $amount, bool $withCurrency = true, bool $capitalize = true): string
    {
        $normalized = number_format((float)$amount, 2, '.', '');
        [$int, $dec] = explode('.', $normalized);

        $result = trim(self::terbilang((int)$int));
        if ($withCurrency) {
            $result .= ' rupiah';
        }

        if ($dec !== '00') {
            $result .= ' ' . trim(self::terbilang((int)$dec)) . ' sen';
        }

        $result = preg_replace('/\s+/', ' ', trim($result));
        return $capitalize ? mb_convert_case($result, MB_CASE_TITLE, 'UTF-8') : $result;
    }

    public static function generateFromPembayaran(string $kode_pembayaran, bool $withCurrency = true, bool $capitalize = true): string
    {
        $pembayaran = Pembayaran::findOrFail($kode_pembayaran);
        return self::toWords($pembayaran->jumlah, $withCurrency, $capitalize);
    }

    private static function terbilang(int $nilai): string
    {
        if ($nilai === 0) {
            return 'nol';
        }

        if ($nilai < 0) {
            return 'minus ' . self::terbilang(abs($nilai));
        }

        $huruf = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($nilai < 12) {
            return $huruf[$nilai];
        }
        if ($nilai < 20) {
            return $huruf[$nilai - 10] . ' belas';
        }
        if ($nilai < 100) {
            $puluh = intdiv($nilai, 10);
            $sisa = $nilai % 10;
            return self::terbilang($puluh) . ' puluh' . ($sisa ? ' ' . self::terbilang($sisa) : '');
        }
        if ($nilai < 200) { // 100 - 199
            $sisa = $nilai - 100;
            return 'seratus' . ($sisa ? ' ' . self::terbilang($sisa) : '');
        }
        if ($nilai < 1000) { // 200 - 999
            $ratus = intdiv($nilai, 100);
            $sisa = $nilai % 100;
            return self::terbilang($ratus) . ' ratus' . ($sisa ? ' ' . self::terbilang($sisa) : '');
        }
        if ($nilai < 2000) { // 1000 - 1999
            $sisa = $nilai - 1000;
            return 'seribu' . ($sisa ? ' ' . self::terbilang($sisa) : '');
        }
        if ($nilai < 1000000) { // ribu
            $ribu = intdiv($nilai, 1000);
            $sisa = $nilai % 1000;
            return self::terbilang($ribu) . ' ribu' . ($sisa ? ' ' . self::terbilang($sisa) : '');
        }
        if ($nilai < 1000000000) { // juta
            $juta = intdiv($nilai, 1000000);
            $sisa = $nilai % 1000000;
            return self::terbilang($juta) . ' juta' . ($sisa ? ' ' . self::terbilang($sisa) : '');
        }
        if ($nilai < 1000000000000) { // miliar
            $miliar = intdiv($nilai, 1000000000);
            $sisa = $nilai % 1000000000;
            return self::terbilang($miliar) . ' miliar' . ($sisa ? ' ' . self::terbilang($sisa) : '');
        }
        if ($nilai < 1000000000000000) { // triliun
            $triliun = intdiv($nilai, 1000000000000);
            $sisa = $nilai % 1000000000000;
            return self::terbilang($triliun) . ' triliun' . ($sisa ? ' ' . self::terbilang($sisa) : '');
        }
        $kuadriliun = intdiv($nilai, 1000000000000000);
        $sisa = $nilai % 1000000000000000;
        return self::terbilang($kuadriliun) . ' kuadriliun' . ($sisa ? ' ' . self::terbilang($sisa) : '');
    }
}
