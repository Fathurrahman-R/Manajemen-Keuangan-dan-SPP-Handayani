<?php

use App\Livewire\RekapBulanan;
use Carbon\Carbon;

function resolveBulanTahun(string $label, string $currentMonthYear): array
{
    $component = new RekapBulanan;
    $component->currentMonthYear = $currentMonthYear;

    $method = new ReflectionMethod(RekapBulanan::class, 'resolveBulanTahun');
    $method->setAccessible(true);

    return $method->invoke($component, $label);
}

it('resolves bulan and tahun from the "F Y" label sent by KasController', function () {
    Carbon::setTestNow('2026-08-20');

    expect(resolveBulanTahun('Juli 2026', '2026-08-20'))->toBe([7, 2026]);
    expect(resolveBulanTahun('Agustus 2026', '2026-08-20'))->toBe([8, 2026]);
    expect(resolveBulanTahun('Desember 2025', '2026-08-20'))->toBe([12, 2025]);

    Carbon::setTestNow();
});

it('still resolves a bare month name using the current year', function () {
    Carbon::setTestNow('2026-08-20');

    expect(resolveBulanTahun('Juli', '2026-08-20'))->toBe([7, 2026]);

    Carbon::setTestNow();
});
