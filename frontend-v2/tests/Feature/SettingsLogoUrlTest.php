<?php

use App\Filament\Pages\Settings;

test('logo URL mengikuti host backend dari config, bukan 127.0.0.1 hardcoded', function () {
    config()->set('handayani.storage_url', 'https://sekolah.example.com/storage');

    $url = Settings::logoUrl('logo-sekolah/abc.jpg');

    expect($url)->toStartWith('https://sekolah.example.com/storage/logo-sekolah/abc.jpg?v=');
});

test('logo URL tidak menghasilkan slash ganda', function () {
    config()->set('handayani.storage_url', 'https://sekolah.example.com/storage/');

    $url = Settings::logoUrl('/logo-sekolah/abc.jpg');

    expect($url)->toStartWith('https://sekolah.example.com/storage/logo-sekolah/abc.jpg?v=')
        ->and($url)->not->toContain('storage//');
});

test('storage_url menunjuk ke storage publik backend, bukan endpoint API', function () {
    $config = require base_path('config/handayani.php');

    expect($config['storage_url'])->toStartWith('http')
        ->and($config['storage_url'])->toEndWith('/storage')
        ->and($config['storage_url'])->not->toContain('/api');
});

test('tanpa override BACKEND_STORAGE_URL, storage_url diturunkan dari api_url', function () {
    $backup = [
        'API_URL' => $_ENV['API_URL'] ?? null,
        'BACKEND_STORAGE_URL' => $_ENV['BACKEND_STORAGE_URL'] ?? null,
    ];

    $_ENV['API_URL'] = $_SERVER['API_URL'] = 'https://sekolah.example.com/api';
    putenv('API_URL=https://sekolah.example.com/api');
    unset($_ENV['BACKEND_STORAGE_URL'], $_SERVER['BACKEND_STORAGE_URL']);
    putenv('BACKEND_STORAGE_URL');

    try {
        $config = require base_path('config/handayani.php');

        expect($config['storage_url'])->toBe('https://sekolah.example.com/storage');
    } finally {
        foreach ($backup as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);

                continue;
            }

            $_ENV[$key] = $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
});
