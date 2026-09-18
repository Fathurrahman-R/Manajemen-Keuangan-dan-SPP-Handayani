<?php

use App\Services\BrandingService;
use Illuminate\Support\Facades\Http;

/**
 * Reset memoisasi request-scoped BrandingService. Panel provider sudah memanggil
 * get() saat boot, jadi tanpa reset ini test cuma kena cache hit dan tidak pernah
 * melihat panggilan HTTP yang sebenarnya ingin diuji.
 */
function resetBrandingMemo(): void
{
    $property = new ReflectionProperty(BrandingService::class, 'memoized');
    $property->setAccessible(true);
    $property->setValue(null, null);
}

test('BrandingService tidak menembak API sama sekali', function () {
    resetBrandingMemo();
    session()->forget('data.branding');
    Http::fake();

    $config = BrandingService::get();

    Http::assertNothingSent();

    expect($config->hasBranding())->toBeFalse()
        ->and($config->branchName)->toBe('Handayani');
});
