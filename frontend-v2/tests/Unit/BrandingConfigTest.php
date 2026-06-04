<?php

use App\Services\BrandingConfig;

test('fromApiResponse creates config from API data', function () {
    $data = [
        'logo_url' => 'https://example.com/logo.png',
        'primary_color' => '#1e40af',
        'favicon_url' => 'https://example.com/favicon.ico',
        'branch_name' => 'Sekolah ABC',
        'logo_base64' => 'base64data',
    ];

    $config = BrandingConfig::fromApiResponse($data);

    expect($config->logoUrl)->toBe('https://example.com/logo.png');
    expect($config->primaryColor)->toBe('#1e40af');
    expect($config->faviconUrl)->toBe('https://example.com/favicon.ico');
    expect($config->branchName)->toBe('Sekolah ABC');
    expect($config->logoBase64)->toBe('base64data');
});

test('fromApiResponse handles missing fields gracefully', function () {
    $config = BrandingConfig::fromApiResponse([]);

    expect($config->logoUrl)->toBeNull();
    expect($config->primaryColor)->toBeNull();
    expect($config->faviconUrl)->toBeNull();
    expect($config->branchName)->toBe('Handayani');
    expect($config->logoBase64)->toBeNull();
});

test('default returns config with no custom branding', function () {
    $config = BrandingConfig::default();

    expect($config->logoUrl)->toBeNull();
    expect($config->primaryColor)->toBeNull();
    expect($config->faviconUrl)->toBeNull();
    expect($config->branchName)->toBe('Handayani');
    expect($config->hasBranding())->toBeFalse();
});

test('hasBranding returns true when logo is set', function () {
    $config = BrandingConfig::fromApiResponse(['logo_url' => 'https://example.com/logo.png']);
    expect($config->hasBranding())->toBeTrue();
});

test('hasBranding returns true when primary color is set', function () {
    $config = BrandingConfig::fromApiResponse(['primary_color' => '#ff0000']);
    expect($config->hasBranding())->toBeTrue();
});

test('hasBranding returns true when favicon is set', function () {
    $config = BrandingConfig::fromApiResponse(['favicon_url' => 'https://example.com/fav.ico']);
    expect($config->hasBranding())->toBeTrue();
});

test('primaryColorRgb converts 6-digit hex to RGB string', function () {
    $config = BrandingConfig::fromApiResponse(['primary_color' => '#1e40af']);
    expect($config->primaryColorRgb())->toBe('30, 64, 175');
});

test('primaryColorRgb converts 3-digit shorthand hex to RGB string', function () {
    $config = BrandingConfig::fromApiResponse(['primary_color' => '#f00']);
    expect($config->primaryColorRgb())->toBe('255, 0, 0');
});

test('primaryColorRgb handles hex without hash prefix', function () {
    $config = BrandingConfig::fromApiResponse(['primary_color' => '1e40af']);
    expect($config->primaryColorRgb())->toBe('30, 64, 175');
});

test('primaryColorRgb returns null when no color set', function () {
    $config = BrandingConfig::default();
    expect($config->primaryColorRgb())->toBeNull();
});

test('primaryColorRgb returns null for invalid length hex', function () {
    $config = BrandingConfig::fromApiResponse(['primary_color' => '#ab']);
    expect($config->primaryColorRgb())->toBeNull();
});
