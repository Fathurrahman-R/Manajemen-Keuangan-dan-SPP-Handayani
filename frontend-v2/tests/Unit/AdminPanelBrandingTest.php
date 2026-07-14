<?php

use App\Services\BrandingConfig;

test('applyBranchBranding does nothing when no branding is configured', function () {
    // When default branding (no custom), hasBranding returns false
    $config = BrandingConfig::default();

    expect($config->hasBranding())->toBeFalse();
});

test('branding config provides correct data for panel theming with primary color', function () {
    $config = BrandingConfig::fromApiResponse([
        'primary_color' => '#1e40af',
        'branch_name' => 'Sekolah ABC',
    ]);

    expect($config->hasBranding())->toBeTrue();
    expect($config->primaryColor)->toBe('#1e40af');
    expect($config->primaryColorRgb())->toBe('30, 64, 175');
    expect($config->logoUrl)->toBeNull();
    expect($config->faviconUrl)->toBeNull();
});

test('branding config provides logo URL for brand logo', function () {
    $config = BrandingConfig::fromApiResponse([
        'logo_url' => 'https://example.com/logo.png',
        'branch_name' => 'Sekolah ABC',
    ]);

    expect($config->hasBranding())->toBeTrue();
    expect($config->logoUrl)->toBe('https://example.com/logo.png');
});

test('branding config provides fallback brand name when no logo', function () {
    $config = BrandingConfig::fromApiResponse([
        'primary_color' => '#ff5500',
        'branch_name' => 'Sekolah XYZ',
    ]);

    expect($config->hasBranding())->toBeTrue();
    expect($config->logoUrl)->toBeNull();
    expect($config->branchName)->toBe('Sekolah XYZ');
});

test('branding config provides favicon URL for panel favicon', function () {
    $config = BrandingConfig::fromApiResponse([
        'favicon_url' => 'https://example.com/favicon.ico',
    ]);

    expect($config->hasBranding())->toBeTrue();
    expect($config->faviconUrl)->toBe('https://example.com/favicon.ico');
});

test('full branding config provides all panel theming values', function () {
    $config = BrandingConfig::fromApiResponse([
        'logo_url' => 'https://example.com/logo.png',
        'primary_color' => '#e11d48',
        'favicon_url' => 'https://example.com/favicon.ico',
        'branch_name' => 'Sekolah Handayani Cabang 2',
    ]);

    expect($config->hasBranding())->toBeTrue();
    expect($config->logoUrl)->toBe('https://example.com/logo.png');
    expect($config->primaryColor)->toBe('#e11d48');
    expect($config->primaryColorRgb())->toBe('225, 29, 72');
    expect($config->faviconUrl)->toBe('https://example.com/favicon.ico');
    expect($config->branchName)->toBe('Sekolah Handayani Cabang 2');
});
