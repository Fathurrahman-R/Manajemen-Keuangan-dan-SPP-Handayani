<?php

namespace Database\Seeders\Support;

use App\Models\TahunAjaran;
use Carbon\Carbon;

/**
 * Aturan penamaan dan penjadwalan jenis tagihan, dipakai bersama oleh
 * JenisTagihanSeeder (yang membuatnya) dan TagihanSeeder (yang menagihkannya).
 *
 * Keduanya harus sepakat persis soal nama. Tabel `jenis_tagihans` tidak punya
 * kolom jenjang maupun kategori, jadi satu-satunya penanda "tagihan ini untuk
 * siapa" adalah namanya. Kalau tiap seeder menyusun atau mengurai nama dengan
 * caranya sendiri, ketidakcocokan sekecil apa pun berujung siswa tidak
 * tertagih tanpa error apa pun — kegagalan yang sepenuhnya senyap.
 *
 * Mencocokkan dengan pencarian substring juga tidak aman: kategori "Yatim"
 * adalah awalan dari "Yatim Piatu", sehingga siswa yatim piatu yang seharusnya
 * bebas SPP bisa ikut tertagih tarif yatim.
 */
class SkemaTagihan
{
    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /** SPP jatuh tempo tanggal 10 tiap bulan. */
    public const TANGGAL_JATUH_TEMPO_SPP = 10;

    /**
     * Bulan-bulan dalam satu tahun ajaran yang tagihan SPP-nya sudah
     * diterbitkan, yaitu yang jatuh temponya sudah lewat.
     *
     * Tagihan sengaja tidak diterbitkan mendahului jatuh tempo. KPI "Siswa
     * Menunggak" di DashboardService::getSummary() menghitung setiap tagihan
     * berstatus bukan-Lunas sebagai tunggakan tanpa memeriksa jatuh tempo,
     * sehingga SPP bulan berjalan yang belum waktunya dibayar pun membuat
     * hampir seluruh siswa tampak menunggak.
     *
     * @return list<Carbon> awal tiap bulan
     */
    public static function bulanTerbit(TahunAjaran $tahunAjaran): array
    {
        $mulai = Carbon::parse($tahunAjaran->tanggal_mulai)->startOfMonth();
        $selesai = Carbon::parse($tahunAjaran->tanggal_selesai)->startOfMonth();
        $hasil = [];

        for ($bulan = $mulai->copy(); $bulan->lessThanOrEqualTo($selesai); $bulan->addMonth()) {
            if (self::jatuhTempoSpp($bulan)->isFuture()) {
                break;
            }

            $hasil[] = $bulan->copy();
        }

        return $hasil;
    }

    public static function jatuhTempoSpp(Carbon $bulan): Carbon
    {
        return $bulan->copy()->day(self::TANGGAL_JATUH_TEMPO_SPP);
    }

    public static function namaSpp(string $jenjang, string $kategori, Carbon $bulan): string
    {
        return "SPP {$jenjang} {$kategori} ".self::NAMA_BULAN[$bulan->month].' '.$bulan->year;
    }

    /**
     * Tagihan sekali setahun, menumpuk di awal tahun ajaran seperti kebiasaan
     * sekolah menagih uang pangkal, seragam, dan buku saat siswa masuk.
     *
     * @return list<array{label: string, tarif: array<string, int>, tempo: Carbon, hanya_siswa_baru: ?bool}>
     */
    public static function tagihanTahunan(TahunAjaran $tahunAjaran): array
    {
        $awal = Carbon::parse($tahunAjaran->tanggal_mulai);

        $daftar = [
            ['label' => 'Uang Pangkal', 'tarif' => TarifDemo::UANG_PANGKAL, 'tempo' => $awal->copy()->day(15), 'hanya_siswa_baru' => true],
            ['label' => 'Seragam', 'tarif' => TarifDemo::SERAGAM, 'tempo' => $awal->copy()->addMonth()->day(5), 'hanya_siswa_baru' => true],
            ['label' => 'Daftar Ulang', 'tarif' => TarifDemo::DAFTAR_ULANG, 'tempo' => $awal->copy()->day(20), 'hanya_siswa_baru' => false],
            ['label' => 'Buku Paket', 'tarif' => TarifDemo::BUKU_PAKET, 'tempo' => $awal->copy()->addMonth()->day(10), 'hanya_siswa_baru' => null],
            ['label' => 'Kegiatan dan Ekstrakurikuler', 'tarif' => TarifDemo::KEGIATAN, 'tempo' => $awal->copy()->addMonth()->day(20), 'hanya_siswa_baru' => null],
        ];

        // Alasan sama dengan SPP: yang belum jatuh tempo belum diterbitkan.
        return array_values(array_filter($daftar, fn (array $item): bool => ! $item['tempo']->isFuture()));
    }

    public static function namaTahunan(string $label, string $jenjang, string $namaTahunAjaran): string
    {
        return "{$label} {$jenjang} {$namaTahunAjaran}";
    }
}
