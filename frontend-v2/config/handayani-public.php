<?php

return [
    'name' => env('HANDAYANI_PUBLIC_NAME', 'Lembaga Pendidikan Anak Handayani'),
    'short_name' => env('HANDAYANI_PUBLIC_SHORT_NAME', 'Handayani'),
    'tagline' => env('HANDAYANI_PUBLIC_TAGLINE', 'Membentuk Generasi Berilmu dan Berakhlak'),
    'address' => env('HANDAYANI_PUBLIC_ADDRESS', 'Jl. Pendidikan Islam No. 45, Jakarta Selatan, DKI Jakarta 12345'),
    'phone' => env('HANDAYANI_PUBLIC_PHONE', '(021) 1234-5678'),
    'email' => env('HANDAYANI_PUBLIC_EMAIL', 'info@handayani.sch.id'),
    'whatsapp_number' => env('HANDAYANI_PUBLIC_WHATSAPP', '6281234567890'),
    'spp_portal_url' => env('HANDAYANI_PUBLIC_SPP_PORTAL_URL', '/login'),
    
    'logo' => env('HANDAYANI_PUBLIC_LOGO', 'images/logo.jpg'), // Default menggunakan asset lokal
    
    'colors' => [
        'primary' => env('HANDAYANI_PUBLIC_COLOR_PRIMARY', '#1B4FBF'), // Biru Handayani
        'accent'  => env('HANDAYANI_PUBLIC_COLOR_ACCENT', '#0D8A6E'),  // Hijau Teal Handayani
    ],
    
    // --- CONTENT HERO ---
    'hero' => [
        'stats' => [
            ['key' => '3', 'value' => 'Jenjang Terpadu'],
            ['key' => '20+', 'value' => 'Tahun Berdiri'],
            ['key' => '100%', 'value' => 'Kurikulum Nasional'],
        ],
        'images' => [
            'images/hero-illustration.jpg',
            'https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&w=1024&q=80',
            'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1024&q=80',
        ],
    ],

    // --- CONTENT ABOUT ---
    'about' => [
        'title' => 'Lembaga pendidikan Islam yang fokus pada ilmu, adab, dan masa depan.',
        'misi' => 'Menyelenggarakan pendidikan terpadu berbasis nilai Islam, kurikulum nasional, dan pengembangan karakter — untuk membentuk peserta didik yang berilmu, mandiri, dan berakhlak mulia.',
        'visi' => 'Menjadi yayasan pendidikan Islam rujukan yang melahirkan generasi cerdas spiritual, intelektual, dan sosial — siap berkontribusi bagi umat dan bangsa.',
        'nilai_institusional' => [
            ['n' => '01', 'title' => 'Integritas', 'desc' => 'Kejujuran, amanah, dan tanggung jawab menjadi pondasi setiap pendidik dan peserta didik.'],
            ['n' => '02', 'title' => 'Profesionalisme', 'desc' => 'Tenaga pendidik kompeten dengan kurikulum nasional terstandar dan pengembangan berkelanjutan.'],
            ['n' => '03', 'title' => 'Keteladanan', 'desc' => "Mengutamakan akhlak Qur'ani dalam keseharian, dari guru ke murid, dari sekolah ke keluarga."],
        ],
    ],

    // --- CONTENT JENJANG ---
    'jenjang' => [
        'title' => 'Tiga jenjang. Satu lingkungan pendidikan yang utuh.',
        'description' => 'Setiap jenjang dirancang berkesinambungan — peserta didik tumbuh dalam ekosistem yang konsisten dari usia dini hingga akhir sekolah dasar.',
        'levels' => [
            [
                'code' => 'KB/PAUD',
                'name' => 'Kelompok Bermain & PAUD',
                'age' => 'Usia 2 – 4 Tahun',
                'desc' => 'Tahap awal pengenalan dunia belajar melalui bermain, stimulasi motorik, dan pembiasaan adab harian.',
                'programs' => ['Sensory & motorik halus', 'Pengenalan huruf hijaiyah', 'Doa-doa pendek harian', 'Sosial-emosional'],
            ],
            [
                'code' => 'TK',
                'name' => 'Taman Kanak-Kanak',
                'age' => 'Usia 4 – 6 Tahun',
                'desc' => 'Penguatan kesiapan sekolah dasar dengan metode eksploratif berbasis Kurikulum Merdeka dan nilai Islam.',
                'programs' => ['Calistung ceria', 'Tahfidz Juz 30', 'Bahasa Inggris dasar', 'Outbound & manasik'],
            ],
            [
                'code' => 'MI',
                'name' => 'Madrasah Ibtidaiyah',
                'age' => 'Usia 6 – 12 Tahun',
                'desc' => 'Pendidikan dasar Islam terpadu yang mengintegrasikan kurikulum nasional, tahfidz, dan literasi digital.',
                'programs' => ['Kurikulum Nasional + IT', 'Tahfidz terstruktur', 'Sains bilingual', 'Ekstrakurikuler pilihan'],
            ],
        ],
    ],

    // --- CONTENT SPP CTA ---
    'spp_cta' => [
        'title' => 'Portal Pembayaran SPP',
        'description' => 'Lakukan pembayaran SPP kapan saja melalui portal resmi Yayasan Handayani. Riwayat transparan, konfirmasi otomatis, dan terlindungi enkripsi end-to-end.',
        'badges' => [
            [
                'label' => 'Terverifikasi',
                'desc' => 'Akun siswa diverifikasi langsung oleh admin yayasan.',
                'icon' => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>',
            ],
            [
                'label' => 'Real-time',
                'desc' => 'Status pembayaran tersinkron seketika.',
                'icon' => '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
            ],
            [
                'label' => 'Aman SSL',
                'desc' => 'Enkripsi industri standar untuk setiap transaksi.',
                'icon' => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            ],
        ],
    ],
    
    // --- KONTAK MAP ---
    'branches' => [
        [
            'name' => 'Kampus Pusat Handayani',
            'address' => 'Jl. Pendidikan Islam No. 45, Jakarta Selatan',
            'lat' => -6.230000,
            'lng' => 106.820000,
        ],
        [
            'name' => 'Cabang Handayani',
            'address' => 'Jl. Pendidikan Raya No. 10, Jakarta Timur',
            'lat' => -6.250000,
            'lng' => 106.870000,
        ],
    ],

    'map_settings' => [
        'default_zoom' => 13,
        'max_zoom' => 19,
        'tile_url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        'zoom_control' => true,
        'scroll_wheel_zoom' => false, // false agar saat scroll halaman tidak nyangkut di map
        'padding_fitbounds' => 50,
    ],
];
