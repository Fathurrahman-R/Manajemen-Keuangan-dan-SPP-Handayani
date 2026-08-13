<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Membuat satu pembayaran untuk tiap tagihan yang sudah punya setoran (`tmp`
 * lebih dari nol), dengan tanggal yang selalu masuk akal terhadap jatuh tempo
 * tagihannya.
 *
 * Dua hal yang diperbaiki dari versi sebelumnya:
 *
 * 1. Tanggal bayar dulu diacak dalam rentang tiga bulan terakhir tanpa melihat
 *    jatuh tempo, sehingga muncul pembayaran yang tercatat mendahului tagihan
 *    yang dilunasinya — laporan kas harian jadi mustahil dipertanggungjawabkan.
 * 2. Kolom `pembayar` diisi nama siswa. Yang membayar SPP adalah orang tua atau
 *    wali, jadi kwitansi hasil cetak dulu selalu salah nama.
 */
class PembayaranSeeder extends Seeder
{
    /** @var array<string, int> nomor urut kode pembayaran per prefix bulan */
    private array $urutan = [];

    public function run(): void
    {
        $pembayarPerNis = $this->petaPembayar();
        $buffer = [];

        DB::table('tagihans')
            ->join('jenis_tagihans', 'jenis_tagihans.id', '=', 'tagihans.jenis_tagihan_id')
            ->where('tagihans.tmp', '>', 0)
            ->select(
                'tagihans.kode_tagihan',
                'tagihans.nis',
                'tagihans.tmp',
                'tagihans.branch_id',
                'jenis_tagihans.jatuh_tempo',
            )
            ->orderBy('jenis_tagihans.jatuh_tempo')
            ->chunk(2000, function ($tagihans) use (&$buffer, $pembayarPerNis) {
                foreach ($tagihans as $tagihan) {
                    $tanggal = $this->tanggalBayar($tagihan->jatuh_tempo);

                    $buffer[] = [
                        'kode_pembayaran' => $this->kodePembayaran($tanggal),
                        'kode_tagihan' => $tagihan->kode_tagihan,
                        'tanggal' => $tanggal,
                        'metode' => 'offline',
                        'jumlah' => $tagihan->tmp,
                        'pembayar' => $pembayarPerNis[$tagihan->nis] ?? 'Wali Murid',
                        'branch_id' => $tagihan->branch_id,
                        'created_at' => $tanggal,
                        'updated_at' => $tanggal,
                    ];

                    if (count($buffer) >= 1000) {
                        DB::table('pembayarans')->insert($buffer);
                        $buffer = [];
                    }
                }
            });

        if ($buffer !== []) {
            DB::table('pembayarans')->insert($buffer);
        }
    }

    /**
     * Nama penyetor per NIS: ayah kalau ada, kalau tidak ibu, kalau tidak wali.
     * Urutan ini mengikuti siapa yang lazim mengurus pembayaran di keluarga
     * Indonesia, sekaligus otomatis benar untuk siswa yatim/piatu karena baris
     * orang tua yang tidak ada memang tidak dibuat oleh SiswaSeeder.
     *
     * @return array<string, string>
     */
    private function petaPembayar(): array
    {
        return DB::table('siswas')
            ->leftJoin('ayah', 'ayah.id', '=', 'siswas.ayah_id')
            ->leftJoin('ibu', 'ibu.id', '=', 'siswas.ibu_id')
            ->leftJoin('walis', 'walis.id', '=', 'siswas.wali_id')
            ->select('siswas.nis', 'ayah.nama as ayah', 'ibu.nama as ibu', 'walis.nama as wali')
            ->get()
            ->mapWithKeys(fn ($baris) => [
                $baris->nis => $baris->ayah ?? $baris->ibu ?? $baris->wali ?? 'Wali Murid',
            ])
            ->all();
    }

    /**
     * Orang tua membayar di sekitar jatuh tempo: sebagian beberapa hari lebih
     * awal, sebagian terlambat sampai tiga minggu.
     *
     * Tagihan yang jatuh temponya belum tiba ditangani terpisah. Menjepit
     * tanggalnya ke hari ini — cara paling sederhana untuk mencegah penerimaan
     * bertanggal masa depan — menumpuk ratusan pembayaran pada satu tanggal
     * dan membuat Laporan Kas Harian hari ini melonjak dua puluh kali lipat
     * dibanding hari lain. Karena itu pembayaran awal disebar mundur dalam
     * rentang tiga minggu terakhir.
     */
    private function tanggalBayar(string $jatuhTempo): string
    {
        $hariIni = Carbon::today();
        $tempo = Carbon::parse($jatuhTempo);

        if ($tempo->greaterThan($hariIni)) {
            return $hariIni->copy()->subDays(fake()->numberBetween(0, 21))->format('Y-m-d');
        }

        $tanggal = $tempo->addDays(fake()->numberBetween(-5, 21));

        return $tanggal->greaterThan($hariIni)
            ? $hariIni->copy()->subDays(fake()->numberBetween(0, 7))->format('Y-m-d')
            : $tanggal->format('Y-m-d');
    }

    private function kodePembayaran(string $tanggal): string
    {
        $prefix = 'PAY-'.Carbon::parse($tanggal)->format('ym');
        $this->urutan[$prefix] = ($this->urutan[$prefix] ?? 0) + 1;

        return $prefix.'-'.str_pad((string) $this->urutan[$prefix], 5, '0', STR_PAD_LEFT);
    }
}
