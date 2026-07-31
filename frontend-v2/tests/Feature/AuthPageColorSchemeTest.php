<?php

use App\Filament\Pages\Auth\ForgotPassword;
use App\Filament\Pages\Auth\ResetPassword;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Warna primary panel bersifat dinamis: AdminPanelProvider::resolvePanelColors()
 * memakai warna branding cabang dan hanya jatuh ke #1B4FBF sebagai default.
 * Halaman reset/forgot password dulu menulis hex itu langsung di atribut style,
 * sehingga tombolnya tidak ikut branding, dark mode, maupun state hover/disabled.
 */
test('tombol reset password tidak menulis warna langsung di atribut style', function () {
    Http::fake(['*' => Http::response(['valid' => true], 200)]);

    Livewire::withQueryParams(['token' => 'token-valid'])
        ->test(ResetPassword::class)
        ->assertSee('Reset Password')
        ->assertDontSee('background-color', escape: false);
});

test('tombol forgot password tidak menulis warna langsung di atribut style', function () {
    Livewire::test(ForgotPassword::class)
        ->assertSee('Kirim Link Reset')
        ->assertDontSee('background-color', escape: false);
});

/**
 * Halaman lupa/reset password terdaftar di routes/web.php, di luar route group
 * panel Filament. Tanpa middleware `panel` panelnya tidak pernah di-boot, warna
 * merek tidak terdaftar, dan Filament memakai palet bawaannya (amber) — halaman
 * login biru, halaman reset oranye.
 *
 * 262.727 adalah hue #1B4FBF (warna panel); 58.318 hue amber-600 bawaan Filament.
 */
test('halaman auth di luar panel tetap memakai warna merek, bukan amber bawaan', function (string $url) {
    Http::fake(['*' => Http::response(['valid' => true], 200)]);

    $this->get($url)
        ->assertOk()
        ->assertSee('262.727', escape: false)
        ->assertDontSee('58.318', escape: false);
})->with([
    'forgot password' => '/forgot-password',
    'reset password' => '/reset-password?token=token-valid',
]);

/**
 * Penjaga agar hex merek tidak bocor lagi ke Blade. Sumber kebenaran warna ada di
 * resolvePanelColors() (panel) dan resources/css/public.css (halaman publik).
 */
test('tidak ada view Filament yang menghardcode hex warna merek', function () {
    $pelanggar = [];

    $berkas = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views/filament'))
    );

    foreach ($berkas as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        if (preg_match('/#[0-9a-fA-F]{6}\b/', file_get_contents($file->getPathname()))) {
            $pelanggar[] = str_replace(resource_path('views/'), '', $file->getPathname());
        }
    }

    expect($pelanggar)->toBe([]);
});
