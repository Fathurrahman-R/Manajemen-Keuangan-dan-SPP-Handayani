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
];
