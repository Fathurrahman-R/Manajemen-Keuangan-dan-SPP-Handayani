<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Handayani System & Portal Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains central rollout settings, feature toggles, and UI
    | customizations for the Manajemen Keuangan & SPP Handayani system.
    |
    */

    'features' => [
        // Enable parent/student portal at path '/portal'
        'portal_enabled' => env('HANDAYANI_PORTAL_ENABLED', true),

        // Enable custom reorganized navigation sidebar in the Admin Panel
        'custom_navigation_enabled' => env('HANDAYANI_CUSTOM_NAVIGATION_ENABLED', true),

        // Enable fallback profile page migration to Filament-native EditProfile page
        'profile_migration_enabled' => env('HANDAYANI_PROFILE_MIGRATION_ENABLED', true),

        // Enable loading screen indicators and SPA transition animations
        'spa_loading_enabled' => env('HANDAYANI_SPA_LOADING_ENABLED', true),

        // Enable Midtrans online payment gateway integration
        'midtrans_enabled' => env('HANDAYANI_MIDTRANS_ENABLED', false),
    ],

    'portal' => [
        // Default route prefix for the student/parent portal
        'path' => env('HANDAYANI_PORTAL_PATH', 'portal'),

        // Whether breadcrumbs should be shown in the portal top nav
        'breadcrumbs' => env('HANDAYANI_PORTAL_BREADCRUMBS', false),
    ],

    'midtrans' => [
        // Flat admin fee charged to student per transaction (integer Rupiah)
        'fee_flat' => (int) env('HANDAYANI_MIDTRANS_FEE_FLAT', 4000),

        // Minimum payment amount allowed (integer Rupiah)
        'min_amount' => 10_000,

        // Midtrans Snap.js script URL (sandbox default)
        'snap_url' => env('MIDTRANS_SNAP_URL', 'https://app.sandbox.midtrans.com/snap/snap.js'),

        // Midtrans client key (public, safe for frontend usage)
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
    ],

    'cache' => [
        // TTL (detik) cache respons API dashboard. Lihat App\Services\ApiService::cachedGet().
        // Widget dashboard poll tiap 5s (Filament CanPoll default); TTL menentukan
        // seberapa sering polling itu benar-benar memicu request baru ke backend.
        'dashboard_ttl' => (int) env('DASHBOARD_CACHE_TTL', 60),

        // TTL (detik) cache /rbac/user-resources & /rbac/user-groups (PermissionHelper).
        // Dipanggil di SETIAP navigasi halaman (cek akses nav/aksi) — tanpa cache ini,
        // dua request ~470ms tiap panggilan jadi biaya tetap tiap pindah halaman,
        // di luar biaya data spesifik halaman itu sendiri.
        'rbac_ttl' => (int) env('RBAC_CACHE_TTL', 60),

        // TTL (detik) cache opsi dropdown master data (kelas, kategori, jenis-tagihan,
        // rbac/permissions). Data ini di-refetch berulang tiap modal/wizard step yang
        // beda dibuka (mis. DataSiswa punya 5x panggilan /kelas/{jenjang} identik di
        // form create/edit/wizard yang berbeda) padahal isinya sama & jarang berubah —
        // TTL lebih panjang dari dashboard karena resiko staleness jauh lebih rendah
        // (bukan permukaan CRUD utama, cuma opsi pilihan).
        'master_data_ttl' => (int) env('MASTER_DATA_CACHE_TTL', 300),
    ],
];
