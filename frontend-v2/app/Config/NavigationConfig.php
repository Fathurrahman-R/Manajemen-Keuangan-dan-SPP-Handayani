<?php

namespace App\Config;

class NavigationConfig
{
    /**
     * Navigation groups with their labels, icons, and menu items.
     * Groups are hidden entirely if the user has no permission to any item within.
     */
    public const GROUPS = [
        'akademik' => [
            'label' => 'Akademik',
            'icon' => 'heroicon-o-academic-cap',
            'items' => ['siswa', 'kelas', 'kenaikan-kelas', 'tahun-ajaran'],
        ],
        'keuangan' => [
            'label' => 'Keuangan',
            'icon' => 'heroicon-o-banknotes',
            'items' => ['tagihan', 'pembayaran', 'pengeluaran', 'kas', 'jenis-tagihan'],
        ],
        'laporan' => [
            'label' => 'Laporan',
            'icon' => 'heroicon-o-chart-bar',
            'items' => ['dashboard', 'import-export', 'kas-harian', 'rekap-bulanan'],
        ],
        'pengaturan' => [
            'label' => 'Pengaturan',
            'icon' => 'heroicon-o-cog-6-tooth',
            'items' => ['user-management', 'role-management', 'app-settings', 'notification-settings'],
        ],
    ];

    /**
     * Pages that support jenjang-based sub-navigation.
     * These pages will show KB/TK/MI sub-menu items in the sidebar.
     */
    public const JENJANG_PAGES = [
        'siswa',
        'kelas',
        'tagihan',
        'kenaikan-kelas',
    ];

    /**
     * Available jenjang options for sub-navigation.
     */
    public const JENJANG_OPTIONS = ['KB', 'TK', 'MI'];
}
