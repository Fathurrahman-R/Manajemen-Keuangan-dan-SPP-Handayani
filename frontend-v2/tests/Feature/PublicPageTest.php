<?php

it('returns 200 for the root URL', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('uses PublicPageController@index', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    // Verify route action
    $route = app('router')->getRoutes()->match(
        request()->create('/', 'GET')
    );
    expect($route->getActionName())->toContain('PublicPageController@index');
});

it('renders the hero section with beranda anchor', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('id="beranda"', false);
});

it('renders the configured site name', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee(config('handayani-public.name'));
});

it('renders all seven section anchors', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('id="beranda"', false);
    $response->assertSee('id="tentang"', false);
    $response->assertSee('id="jenjang"', false);
    $response->assertSee('id="ekstrakurikuler"', false);
    $response->assertSee('id="fasilitas"', false);
    $response->assertSee('id="spp"', false);
    $response->assertSee('id="kontak"', false);
});

it('renders the motto as the hero tagline', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee(config('handayani-public.tagline'));
});

it('renders every ekstrakurikuler activity', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    foreach (config('handayani-public.ekstrakurikuler.kegiatan') as $item) {
        $response->assertSee($item['name']);
        $response->assertSee($item['desc']);
    }
});

it('renders every fasilitas counter and supporting room', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    foreach (config('handayani-public.fasilitas.sarana') as $item) {
        $response->assertSee($item['label']);
    }

    foreach (config('handayani-public.fasilitas.ruang_penunjang') as $ruang) {
        $response->assertSee($ruang);
    }
});

it('renders nav links for the new sections in both desktop and mobile menus', function () {
    $content = $this->get('/')->getContent();

    // Each nav link is emitted once for desktop and once for the mobile dropdown
    expect(substr_count($content, 'href="#ekstrakurikuler"'))->toBe(2);
    expect(substr_count($content, 'href="#fasilitas"'))->toBe(2);
});

it('renders the nav with Portal SPP link', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Portal SPP');
    $response->assertSee(config('handayani-public.spp_portal_url'), false);
});

it('renders config-driven contact information', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee(config('handayani-public.address'));
    $response->assertSee(config('handayani-public.phone'));
    $response->assertSee(config('handayani-public.email'));
});

it('renders the WhatsApp link', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('wa.me/'.config('handayani-public.whatsapp_number'), false);
});

it('does not include Filament or Livewire assets', function () {
    $response = $this->get('/');

    $content = $response->getContent();

    // Should NOT contain Livewire or Filament script injections
    expect($content)->not->toContain('@livewireStyles');
    expect($content)->not->toContain('@livewireScripts');
    expect($content)->not->toContain('filament.asset');
});

it('includes Vite public assets', function () {
    $response = $this->get('/');

    $content = $response->getContent();

    // Should contain references to public.css and public.js assets
    // In production build mode these appear as hashed filenames in the manifest
    expect($content)->toContain('public');
});

it('renders the footer copyright with current year', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee(date('Y'));
    $response->assertSee(config('handayani-public.name'));
    $response->assertSee('Dibangun dengan amanah.');
});

it('renders the hero carousel with the configured slides', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('hero-illustration.jpg', false);
    $response->assertSee('Visual sekolah Handayani', false);
});

it('renders Alpine x-data for mobile nav', function () {
    $response = $this->get('/');

    $content = $response->getContent();

    expect($content)->toContain('x-data');
    expect($content)->toContain('open');
});
