<?php

namespace App\Services;

use App\Models\Pembayaran;
use Carbon\Carbon;

class GenerateKeteranganKwitansi
{
    /**
     * Create a new class instance.
     */
    public static function generate(string $kode_pembayaran)
    {
        // Ambil pembayaran beserta relasi yang diperlukan
        $pembayaran = Pembayaran::with(['tagihan.jenis_tagihan', 'tagihan.siswa'])
            ->findOrFail($kode_pembayaran);

        $nama_tagihan = $pembayaran->tagihan->jenis_tagihan->nama;

        $nama_siswa = $pembayaran->tagihan->siswa->nama;

        // Ambil tanggal jatuh tempo dari relasi
        $tanggal = $pembayaran->tagihan->jenis_tagihan->jatuh_tempo;

        // Konversi ke nama bulan bahasa Indonesia
        $bulan = Carbon::parse($tanggal)
            ->locale('id')
            ->translatedFormat('F Y'); // contoh: "Maret"

        $keterangan = "{$nama_tagihan} {$nama_siswa} {$bulan}";
        return $keterangan;
    }
}
