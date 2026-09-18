<?php

namespace Database\Seeders\Support;

/**
 * Sumber data demo yang menyerupai keadaan nyata LPA Handayani di Selatpanjang,
 * Kabupaten Kepulauan Meranti, Riau.
 *
 * Faker locale id_ID tidak cukup di sini: fake()->jobTitle() mengembalikan
 * jabatan korporat berbahasa Inggris, fake()->city() menyebar ke seluruh
 * Indonesia, dan fake()->name() tidak bisa diikat ke jenis kelamin yang sudah
 * ditentukan — semuanya langsung terbaca palsu oleh orang yang mengenal daerah
 * ini. Daftar di bawah dikurasi manual: nama Melayu-Muslim, pekerjaan yang
 * memang ada di kabupaten kepulauan (nelayan, petani sagu, petani karet), dan
 * nama jalan yang benar-benar ada di Selatpanjang.
 */
class DataRealistis
{
    /** @var list<string> */
    private const NAMA_DEPAN_LAKI = [
        'Muhammad', 'Ahmad', 'Abdul', 'Rizki', 'Fadhil', 'Hafiz', 'Ilham', 'Rahmat',
        'Syahrul', 'Zulkifli', 'Iqbal', 'Ridwan', 'Arif', 'Fauzan', 'Habibi', 'Aditya',
        'Bagas', 'Dimas', 'Farhan', 'Gilang', 'Hendra', 'Irfan', 'Junaidi', 'Khairul',
        'Lukman', 'Maulana', 'Nanda', 'Ramadhan', 'Syafiq', 'Taufik', 'Umar', 'Wahyu',
        'Yusuf', 'Zaki', 'Alif', 'Bima', 'Doni', 'Ferdi', 'Hafizh', 'Naufal',
    ];

    /** @var list<string> */
    private const NAMA_DEPAN_PEREMPUAN = [
        'Siti', 'Nur', 'Aisyah', 'Fatimah', 'Khadijah', 'Zahra', 'Nabila', 'Aulia',
        'Salsabila', 'Rahmawati', 'Indah', 'Putri', 'Dinda', 'Anisa', 'Fitri', 'Hasanah',
        'Intan', 'Kartika', 'Lestari', 'Maulida', 'Nadia', 'Oktaviani', 'Permata',
        'Qonita', 'Ratna', 'Syifa', 'Tiara', 'Ulfa', 'Wulandari', 'Yasmin', 'Zulaikha',
        'Amelia', 'Deswita', 'Ghina', 'Humaira', 'Keisha', 'Marwah', 'Najwa', 'Rania',
    ];

    /**
     * Nama kedua/ketiga. Campuran Melayu Riau (Wan, Said, Raja) dan Mandailing
     * yang memang banyak merantau ke pesisir Riau (Nasution, Lubis, Harahap).
     *
     * @var list<string>
     */
    private const NAMA_BELAKANG = [
        'Pratama', 'Saputra', 'Wijaya', 'Kurniawan', 'Setiawan', 'Hidayat', 'Firmansyah',
        'Syahputra', 'Halim', 'Sanjaya', 'Ramadhani', 'Nugraha', 'Yulianto', 'Anggara',
        'Nasution', 'Lubis', 'Harahap', 'Hasibuan', 'Siregar',
        'Wan Abdullah', 'Said Ahmad', 'Raja Ismail', 'Tengku Umar',
        'Alamsyah', 'Maulana', 'Fadillah', 'Rahmadani',
    ];

    /**
     * Akhiran yang dalam penamaan Indonesia hanya dipakai perempuan. Dicampur
     * ke daftar umum akan melahirkan nama seperti "Fauzan Wati" untuk siswa
     * laki-laki — janggal bagi siapa pun yang membaca daftar siswa.
     *
     * @var list<string>
     */
    private const NAMA_BELAKANG_PEREMPUAN = [
        'Syaputri', 'Ningsih', 'Wati', 'Lestari', 'Anggraini', 'Safitri',
    ];

    /**
     * Kepulauan Meranti hidup dari laut, sagu, dan karet — komposisi pekerjaan
     * ayah sengaja mencerminkan itu, bukan sebaran pekerjaan kota besar.
     *
     * @var list<string>
     */
    private const PEKERJAAN_AYAH = [
        'Nelayan', 'Nelayan', 'Nelayan',
        'Petani Sagu', 'Petani Sagu', 'Petani Karet', 'Petani Karet',
        'Wiraswasta', 'Wiraswasta', 'Pedagang', 'Pedagang',
        'Buruh Harian Lepas', 'Buruh Harian Lepas', 'Buruh Bangunan',
        'Karyawan Swasta', 'Karyawan Swasta', 'Sopir', 'Tukang Ojek',
        'Montir', 'Pegawai Toko', 'Pengrajin Kayu',
        'PNS', 'Guru', 'TNI/Polri', 'Perawat',
    ];

    /** @var list<string> */
    private const PEKERJAAN_IBU = [
        'Ibu Rumah Tangga', 'Ibu Rumah Tangga', 'Ibu Rumah Tangga',
        'Ibu Rumah Tangga', 'Ibu Rumah Tangga', 'Ibu Rumah Tangga',
        'Pedagang', 'Pedagang', 'Penjahit', 'Guru', 'Guru',
        'Karyawan Swasta', 'Buruh Tani', 'Bidan', 'PNS', 'Pegawai Toko',
    ];

    /**
     * Pendidikan terakhir orang tua dengan sebaran yang wajar untuk kabupaten
     * kepulauan: mayoritas SMA ke bawah, sarjana minoritas.
     *
     * @var list<string>
     */
    private const PENDIDIKAN = [
        'SD', 'SD', 'SD',
        'SMP', 'SMP', 'SMP', 'SMP',
        'SMA', 'SMA', 'SMA', 'SMA', 'SMA', 'SMA', 'SMA', 'SMA',
        'D3', 'D3',
        'S1', 'S1', 'S1',
        'S2',
    ];

    /** @var list<string> */
    private const TEMPAT_LAHIR = [
        'Selatpanjang', 'Selatpanjang', 'Selatpanjang', 'Selatpanjang', 'Selatpanjang',
        'Tebing Tinggi', 'Tebing Tinggi', 'Merbau', 'Rangsang', 'Tasik Putri Puyu',
        'Pulau Merbau', 'Alah Air', 'Banglas',
        'Bengkalis', 'Dumai', 'Pekanbaru', 'Siak', 'Bagansiapiapi', 'Tanjungpinang', 'Batam',
    ];

    /** @var list<string> */
    private const NAMA_JALAN = [
        'Jl. Ahmad Yani', 'Jl. Diponegoro', 'Jl. Imam Bonjol', 'Jl. Merdeka',
        'Jl. Rumbia', 'Jl. Siak', 'Jl. Kartini', 'Jl. Pelabuhan', 'Jl. Dorak',
        'Jl. Banglas', 'Jl. Alah Air', 'Jl. Tanjung Harapan', 'Jl. Sagu',
        'Jl. Kelapa', 'Jl. Tebing Tinggi', 'Jl. Pembangunan', 'Jl. Rintis',
    ];

    /** @var list<string> */
    private const KELURAHAN = [
        'Selatpanjang Kota', 'Selatpanjang Barat', 'Selatpanjang Timur',
        'Selatpanjang Selatan', 'Banglas', 'Alah Air', 'Gogok', 'Dorak',
    ];

    /**
     * Nama lengkap yang konsisten dengan jenis kelamin. Ini yang tidak bisa
     * dilakukan fake()->name() ketika jenis_kelamin sudah ditentukan lebih dulu:
     * hasilnya "Siti Aminah" berjenis kelamin Laki-laki, yang langsung ketahuan
     * janggal saat data ditampilkan.
     */
    public static function namaLengkap(string $jenisKelamin): string
    {
        $depan = $jenisKelamin === 'Laki-laki'
            ? fake()->randomElement(self::NAMA_DEPAN_LAKI)
            : fake()->randomElement(self::NAMA_DEPAN_PEREMPUAN);

        $belakang = $jenisKelamin === 'Perempuan' && fake()->boolean(30)
            ? fake()->randomElement(self::NAMA_BELAKANG_PEREMPUAN)
            : fake()->randomElement(self::NAMA_BELAKANG);

        // Sebagian anak Melayu memakai tiga suku nama; sebagian cukup dua.
        if (fake()->boolean(35)) {
            $tengah = $jenisKelamin === 'Laki-laki'
                ? fake()->randomElement(self::NAMA_DEPAN_LAKI)
                : fake()->randomElement(self::NAMA_DEPAN_PEREMPUAN);

            return trim("{$depan} {$tengah} {$belakang}");
        }

        return trim("{$depan} {$belakang}");
    }

    public static function pekerjaanAyah(): string
    {
        return fake()->randomElement(self::PEKERJAAN_AYAH);
    }

    public static function pekerjaanIbu(): string
    {
        return fake()->randomElement(self::PEKERJAAN_IBU);
    }

    public static function pendidikan(): string
    {
        return fake()->randomElement(self::PENDIDIKAN);
    }

    public static function tempatLahir(): string
    {
        return fake()->randomElement(self::TEMPAT_LAHIR);
    }

    /**
     * Alamat gaya Indonesia lengkap dengan RT/RW dan kelurahan — bukan
     * fake()->address() yang mencampur format dan menyebar ke luar Riau.
     */
    public static function alamat(): string
    {
        $jalan = fake()->randomElement(self::NAMA_JALAN);
        $nomor = fake()->numberBetween(1, 120);
        $rt = str_pad((string) fake()->numberBetween(1, 12), 2, '0', STR_PAD_LEFT);
        $rw = str_pad((string) fake()->numberBetween(1, 6), 2, '0', STR_PAD_LEFT);
        $kelurahan = fake()->randomElement(self::KELURAHAN);

        return "{$jalan} No. {$nomor}, RT {$rt}/RW {$rw}, Kel. {$kelurahan}, Kec. Tebing Tinggi, Kepulauan Meranti, Riau";
    }

    /**
     * Nomor HP dengan prefix operator yang benar-benar dipakai di Indonesia.
     */
    public static function noHp(): string
    {
        $prefix = fake()->randomElement(['0812', '0813', '0821', '0822', '0852', '0853', '0857', '0895', '0896']);

        return $prefix.fake()->numerify('########');
    }

    /**
     * Email turunan dari nama supaya terlihat seperti email yang benar-benar
     * dibuat orangnya sendiri, bukan string acak.
     */
    public static function emailDari(string $nama): string
    {
        $bagian = preg_split('/\s+/', mb_strtolower($nama)) ?: [];
        $slug = implode('.', array_slice($bagian, 0, 2));
        $slug = preg_replace('/[^a-z.]/', '', $slug) ?? 'pengguna';

        return $slug.fake()->numberBetween(1, 99).'@gmail.com';
    }
}
