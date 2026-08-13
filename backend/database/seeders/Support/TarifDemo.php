<?php

namespace Database\Seeders\Support;

/**
 * Tarif dan ukuran populasi LPA Handayani.
 *
 * SPP berbeda per jenjang DAN per kategori keringanan — bukan satu tarif per
 * jenjang. Angka SPP serta jumlah siswa cabang Selat Panjang di bawah berasal
 * langsung dari keterangan pihak sekolah.
 *
 * Biaya sekali bayar (uang pangkal, seragam, buku, daftar ulang, kegiatan) dan
 * seluruh nominal pengeluaran BELUM dikonfirmasi sekolah; angkanya diturunkan
 * proporsional dari SPP supaya besarannya masuk akal terhadap tarif nyata.
 * Ganti kalau sekolah punya angka sendiri.
 *
 * Semua nilai dalam Rupiah penuh.
 */
class TarifDemo
{
    /**
     * SPP bulanan per jenjang per kategori keringanan.
     *
     * Kategori "Yatim Piatu" dibebaskan sepenuhnya, jadi sengaja tidak
     * dicantumkan: tagihan SPP untuk mereka tidak diterbitkan sama sekali,
     * bukan diterbitkan bernilai nol. KB hanya mengenal kategori Umum.
     *
     * @var array<string, array<string, int>>
     */
    public const SPP_BULANAN = [
        'MI' => [
            'Umum' => 100_000,
            'Bersaudara' => 80_000,
            'Yatim' => 50_000,
            'Piatu' => 50_000,
        ],
        'TK' => [
            'Umum' => 55_000,
            'Bersaudara' => 50_000,
            'Yatim' => 50_000,
            'Piatu' => 50_000,
        ],
        'KB' => [
            'Umum' => 50_000,
        ],
    ];

    /** Kategori yang dibebaskan dari SPP. */
    public const KATEGORI_BEBAS_SPP = 'Yatim Piatu';

    /**
     * Kategori yang boleh dipakai tiap jenjang. KB tidak menerapkan
     * keringanan sama sekali — seluruh siswanya masuk kategori Umum.
     *
     * @var array<string, list<string>>
     */
    public const KATEGORI_PER_JENJANG = [
        'MI' => ['Umum', 'Bersaudara', 'Yatim', 'Piatu', 'Yatim Piatu'],
        'TK' => ['Umum', 'Bersaudara', 'Yatim', 'Piatu', 'Yatim Piatu'],
        'KB' => ['Umum'],
    ];

    /**
     * Jumlah siswa per jenjang per cabang. Selat Panjang memakai angka nyata
     * dari sekolah; dua cabang lain lebih kecil.
     *
     * @var array<string, array<string, int>>
     */
    public const JUMLAH_SISWA = [
        'Selat Panjang' => ['MI' => 100, 'TK' => 58, 'KB' => 6],
        'Desa Kapur' => ['MI' => 42, 'TK' => 24, 'KB' => 4],
        'Darma Putra' => ['MI' => 33, 'TK' => 18, 'KB' => 3],
    ];

    /** Uang pangkal, dibayar sekali saat pertama kali diterima. */
    public const UANG_PANGKAL = ['MI' => 350_000, 'TK' => 200_000, 'KB' => 150_000];

    /** Daftar ulang tiap awal tahun ajaran untuk siswa lama. */
    public const DAFTAR_ULANG = ['MI' => 100_000, 'TK' => 60_000, 'KB' => 50_000];

    /** Seragam, dibeli saat pertama masuk. */
    public const SERAGAM = ['MI' => 200_000, 'TK' => 150_000, 'KB' => 100_000];

    /** Buku paket, tiap awal tahun ajaran. */
    public const BUKU_PAKET = ['MI' => 150_000, 'TK' => 100_000, 'KB' => 75_000];

    /** Kegiatan & ekstrakurikuler, tiap awal tahun ajaran. */
    public const KEGIATAN = ['MI' => 100_000, 'TK' => 60_000, 'KB' => 50_000];

    /**
     * Pengeluaran rutin bulanan untuk cabang seukuran Selat Panjang, yang
     * pemasukan SPP-nya sekitar 13,5 juta per bulan. PengeluaranSeeder
     * menyesuaikan angka ini terhadap jumlah siswa tiap cabang, supaya cabang
     * kecil tidak tercatat belanja sebesar cabang besar dan berakhir merugi.
     *
     * @var list<array{uraian: string, kategori: string, jumlah: int}>
     */
    public const PENGELUARAN_RUTIN = [
        ['uraian' => 'Gaji guru dan staf', 'kategori' => 'Gaji', 'jumlah' => 8_000_000],
        ['uraian' => 'Tagihan listrik dan air', 'kategori' => 'Utilitas', 'jumlah' => 600_000],
        ['uraian' => 'Langganan internet sekolah', 'kategori' => 'Utilitas', 'jumlah' => 300_000],
        ['uraian' => 'Alat tulis kantor', 'kategori' => 'ATK', 'jumlah' => 250_000],
        ['uraian' => 'Honor petugas kebersihan', 'kategori' => 'Kebersihan', 'jumlah' => 400_000],
        ['uraian' => 'Konsumsi rapat guru', 'kategori' => 'Konsumsi', 'jumlah' => 150_000],
    ];

    /**
     * Pengeluaran tidak rutin untuk mengisi alur persetujuan.
     *
     * @var list<array{uraian: string, kategori: string, jumlah: int}>
     */
    public const PENGELUARAN_INSIDENTAL = [
        ['uraian' => 'Perbaikan atap ruang kelas 3 yang bocor', 'kategori' => 'Perbaikan', 'jumlah' => 1_200_000],
        ['uraian' => 'Pembelian 2 unit kipas angin dinding', 'kategori' => 'Inventaris', 'jumlah' => 450_000],
        ['uraian' => 'Servis dan isi ulang tabung pemadam api', 'kategori' => 'Perbaikan', 'jumlah' => 300_000],
        ['uraian' => 'Cetak spanduk penerimaan siswa baru', 'kategori' => 'Promosi', 'jumlah' => 250_000],
        ['uraian' => 'Pembelian buku referensi guru', 'kategori' => 'ATK', 'jumlah' => 350_000],
        ['uraian' => 'Transport rapat koordinasi ke Dinas Pendidikan', 'kategori' => 'Transportasi', 'jumlah' => 300_000],
        ['uraian' => 'Perbaikan instalasi air kamar mandi siswa', 'kategori' => 'Perbaikan', 'jumlah' => 600_000],
        ['uraian' => 'Pembelian tinta printer dan kertas A4', 'kategori' => 'ATK', 'jumlah' => 400_000],
        ['uraian' => 'Konsumsi kegiatan peringatan Maulid Nabi', 'kategori' => 'Konsumsi', 'jumlah' => 900_000],
        ['uraian' => 'Pengecatan ulang pagar sekolah', 'kategori' => 'Perbaikan', 'jumlah' => 1_500_000],
    ];
}
