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
    expect(config('handayani-public.name'))->toBe('Lembaga Pendidikan Anak Handayani');
    expect(config('handayani-public.short_name'))->toBe('Handayani');
    expect(config('handayani-public.tagline'))->toBe("Mandiri, Intelektual, Smart, Hafiz Qur'an");
    expect(config('handayani-public.address'))->toContain('Jl. Selat Panjang No.357');
    expect(config('handayani-public.email'))->toBe('info@handayani.sch.id');
    expect(config('handayani-public.whatsapp_number'))->toBe('6281234567890');
});

it('has a spp_portal_url that defaults to /login', function () {
    expect(config('handayani-public.spp_portal_url'))->toBe('/login');
});

it('all scalar identity values are strings', function () {
    $scalarKeys = ['name', 'short_name', 'tagline', 'address', 'phone', 'email', 'whatsapp_number', 'spp_portal_url'];

    foreach ($scalarKeys as $key) {
        expect(config("handayani-public.{$key}"))->toBeString("Config key '{$key}' should be a string");
    }
});

it('reports the madrasah founding year of 2017 in the hero stats', function () {
    $stats = collect(config('handayani-public.hero.stats'))->pluck('key', 'value');

    expect($stats->get('Tahun Berdiri'))->toBe('2017');
});

it('lists every ekstrakurikuler with a name, sasaran and sifat', function () {
    $kegiatan = config('handayani-public.ekstrakurikuler.kegiatan');

    expect($kegiatan)->toHaveCount(5);

    foreach ($kegiatan as $item) {
        expect($item['name'])->toBeString()->not->toBeEmpty();
        expect($item['sasaran'])->toBeString()->not->toBeEmpty();
        expect($item['sifat'])->toBeIn(['Wajib', 'Pilihan']);
        expect($item['desc'])->toBeString()->not->toBeEmpty();
    }
});

it('marks Pramuka as the only mandatory ekstrakurikuler', function () {
    $wajib = collect(config('handayani-public.ekstrakurikuler.kegiatan'))
        ->where('sifat', 'Wajib')
        ->pluck('name');

    expect($wajib->all())->toBe(['Pramuka']);
});

it('lists fasilitas without exposing equipment brands or damage status', function () {
    $rendered = json_encode(config('handayani-public.fasilitas'));

    expect(config('handayani-public.fasilitas.sarana'))->not->toBeEmpty();
    expect(config('handayani-public.fasilitas.ruang_penunjang'))->not->toBeEmpty();

    foreach (['Epson', 'Eyota', 'Zyrex', 'Cosmos', 'GMC', 'Rusak'] as $forbidden) {
        expect($rendered)->not->toContain($forbidden);
    }
});

it('exposes nav links covering the two new sections', function () {
    $hrefs = collect(config('handayani-public.nav_links'))->pluck('href');

    expect($hrefs)->toContain('#ekstrakurikuler');
    expect($hrefs)->toContain('#fasilitas');
});
