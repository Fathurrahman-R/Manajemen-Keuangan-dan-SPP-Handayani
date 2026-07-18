<?php

it('has the handayani-public config file', function () {
    $config = config('handayani-public');

    expect($config)->toBeArray();
});

it('contains all required config keys', function () {
    $required = [
        'name',
        'short_name',
        'tagline',
        'address',
        'phone',
        'email',
        'whatsapp_number',
        'spp_portal_url',
    ];

    foreach ($required as $key) {
        expect(config("handayani-public.{$key}"))
            ->not->toBeNull("Config key 'handayani-public.{$key}' should not be null");
    }
});

it('has default values matching the reference SITE config', function () {
    expect(config('handayani-public.name'))->toBe('Yayasan Lembaga Pendidikan Anak Handayani');
    expect(config('handayani-public.short_name'))->toBe('Handayani');
    expect(config('handayani-public.tagline'))->toBe('Membentuk Generasi Berilmu dan Berakhlak');
    expect(config('handayani-public.address'))->toBe('Jl. Pendidikan Islam No. 45, Jakarta Selatan, DKI Jakarta 12345');
    expect(config('handayani-public.phone'))->toBe('(021) 1234-5678');
    expect(config('handayani-public.email'))->toBe('info@handayani.sch.id');
    expect(config('handayani-public.whatsapp_number'))->toBe('6281234567890');
});

it('has a spp_portal_url that defaults to /login', function () {
    expect(config('handayani-public.spp_portal_url'))->toBe('/login');
});

it('all config values are strings', function () {
    $config = config('handayani-public');

    foreach ($config as $key => $value) {
        expect($value)->toBeString("Config key '{$key}' should be a string");
    }
});
