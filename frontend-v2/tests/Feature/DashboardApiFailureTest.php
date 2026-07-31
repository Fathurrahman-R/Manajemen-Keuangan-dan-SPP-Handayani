<?php

use App\Filament\Widgets\DashboardAllTimeStatsWidget;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Services\ApiService;
use Illuminate\Support\Facades\Http;

/**
 * Saat panggilan API gagal, widget dulu menampilkan "Rp 0" — tidak bisa
 * dibedakan dari data yang memang nol. Admin bisa menyimpulkan sekolah tidak
 * punya tagihan sama sekali padahal yang terjadi cuma request gagal.
 */
function statValues(array $stats): string
{
    return collect($stats)
        ->map(fn ($stat) => $stat->getValue())
        ->implode(' | ');
}

test('widget all-time menandai data gagal dimuat, bukan menampilkan Rp 0', function () {
    Http::fake(['*' => Http::response(['message' => 'Forbidden'], 403)]);

    $widget = new DashboardAllTimeStatsWidget;
    $stats = (fn () => $this->getStats())->call($widget);

    $nilai = statValues($stats);

    expect($nilai)->toContain('Tidak tersedia');
    expect($nilai)->not->toContain('Rp 0');
});

test('widget statistik periode menandai data gagal dimuat', function () {
    Http::fake(['*' => Http::response(['message' => 'Server Error'], 500)]);

    $widget = new DashboardStatsWidget;
    $stats = (fn () => $this->getStats())->call($widget);

    $nilai = statValues($stats);

    expect($nilai)->toContain('Tidak tersedia');
    expect($nilai)->not->toContain('Rp 0');
});

test('kegagalan tidak ikut tersimpan di cache dashboard', function () {
    Http::fakeSequence()
        ->push(['message' => 'Forbidden'], 403)
        ->push(['data' => ['summary' => ['total_tagihan' => 500]]], 200);

    expect(ApiService::cachedGet('/dashboard/overview'))->toBeNull();

    // Request berikutnya harus benar-benar memanggil API lagi, bukan membaca
    // "null" yang tersimpan dan membeku selama satu TTL penuh.
    expect(ApiService::cachedGet('/dashboard/overview'))
        ->toBe(['summary' => ['total_tagihan' => 500]]);
});
