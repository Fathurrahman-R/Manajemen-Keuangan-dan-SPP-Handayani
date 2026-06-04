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
    ],

    'portal' => [
        // Default route prefix for the student/parent portal
        'path' => env('HANDAYANI_PORTAL_PATH', 'portal'),

        // Whether breadcrumbs should be shown in the portal top nav
        'breadcrumbs' => env('HANDAYANI_PORTAL_BREADCRUMBS', false),
    ],
];
