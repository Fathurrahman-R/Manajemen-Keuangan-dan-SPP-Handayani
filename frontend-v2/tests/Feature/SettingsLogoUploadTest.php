<?php

use App\Filament\Pages\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

test('logo yang diunggah ikut terkirim sebagai multipart ke endpoint /setting', function () {
    Session::put('data.roles', ['superadmin']);
    Session::put('data.permissions', []);

    Http::fake(function ($request) {
        if (str_contains($request->url(), '/setting')) {
            return Http::response(['data' => [
                'id' => 1,
                'nama_sekolah' => 'Sekolah A',
                'lokasi' => 'Kota',
                'alamat' => 'Jalan',
                'email' => 'a@a.com',
                'telepon' => '0812',
                'kepala_sekolah' => 'KS',
                'bendahara' => 'BH',
                'kode_pos' => '12345',
                'logo' => 'logo-sekolah/lama.png',
                'branch_id' => 1,
            ]], 200);
        }

        return Http::response(['data' => []], 200);
    });

    $file = UploadedFile::fake()->image('logo-baru.png', 100, 100);

    Livewire::test(Settings::class)
        ->callAction('updateSetting', data: [
            'nama_sekolah' => 'Sekolah A',
            'lokasi' => 'Kota',
            'alamat' => 'Jalan',
            'email' => 'a@a.com',
            'telepon' => '0812',
            'kepala_sekolah' => 'KS',
            'bendahara' => 'BH',
            'kode_pos' => '12345',
            'logo' => [$file],
        ])
        ->assertHasNoActionErrors();

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && str_contains($request->url(), '/setting/')
        && collect($request->data())->contains(fn ($part) => ($part['name'] ?? null) === 'logo'
            && isset($part['filename'])));
});

test('logo yang tampil ikut path baru setelah backend mengembalikan logo yang sudah diganti', function () {
    Session::put('data.roles', ['superadmin']);
    Session::put('data.permissions', []);
    config()->set('handayani.storage_url', 'https://backend.example.com/storage');

    $logo = 'logo-sekolah/lama.png';

    Http::fake(function ($request) use (&$logo) {
        if ($request->method() === 'POST' && str_contains($request->url(), '/setting/')) {
            $logo = 'logo-sekolah/baru.png';
        }

        return Http::response(['data' => [
            'id' => 1,
            'nama_sekolah' => 'Sekolah A',
            'lokasi' => 'Kota',
            'alamat' => 'Jalan',
            'email' => 'a@a.com',
            'telepon' => '0812',
            'kepala_sekolah' => 'KS',
            'bendahara' => 'BH',
            'kode_pos' => '12345',
            'logo' => $logo,
            'branch_id' => 1,
        ]], 200);
    });

    $component = Livewire::test(Settings::class);

    expect($component->get('setting')['logo'])->toBe('logo-sekolah/lama.png');

    $component->callAction('updateSetting', data: [
        'nama_sekolah' => 'Sekolah A',
        'lokasi' => 'Kota',
        'alamat' => 'Jalan',
        'email' => 'a@a.com',
        'telepon' => '0812',
        'kepala_sekolah' => 'KS',
        'bendahara' => 'BH',
        'kode_pos' => '12345',
        'logo' => [UploadedFile::fake()->image('logo-baru.png', 100, 100)],
    ]);

    expect($component->get('setting')['logo'])->toBe('logo-sekolah/baru.png');

    $component->assertSee('logo-sekolah/baru.png', escape: false);
});
