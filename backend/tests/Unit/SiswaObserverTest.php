<?php

namespace Tests\Unit;

use App\Models\Siswa;
use App\Observers\SiswaObserver;
use App\Services\AkunSiswaService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SiswaObserverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function createSiswaWithDirtyStatus(string $newStatus, string $originalStatus = 'Aktif'): Siswa
    {
        $siswa = new Siswa();
        $siswa->forceFill(['status' => $originalStatus]);
        $siswa->syncOriginal();
        $siswa->status = $newStatus;

        return $siswa;
    }

    private function createSiswaWithoutDirtyStatus(string $status): Siswa
    {
        $siswa = new Siswa();
        $siswa->forceFill(['status' => $status]);
        $siswa->syncOriginal();

        return $siswa;
    }

    public function test_deactivates_account_when_status_changes_to_lulus(): void
    {
        $service = Mockery::mock(AkunSiswaService::class);
        $siswa = $this->createSiswaWithDirtyStatus('Lulus');

        $service->shouldReceive('deactivateAccount')->once()->with($siswa);

        $observer = new SiswaObserver($service);
        $observer->updated($siswa);
    }

    public function test_deactivates_account_when_status_changes_to_pindah(): void
    {
        $service = Mockery::mock(AkunSiswaService::class);
        $siswa = $this->createSiswaWithDirtyStatus('Pindah');

        $service->shouldReceive('deactivateAccount')->once()->with($siswa);

        $observer = new SiswaObserver($service);
        $observer->updated($siswa);
    }

    public function test_deactivates_account_when_status_changes_to_keluar(): void
    {
        $service = Mockery::mock(AkunSiswaService::class);
        $siswa = $this->createSiswaWithDirtyStatus('Keluar');

        $service->shouldReceive('deactivateAccount')->once()->with($siswa);

        $observer = new SiswaObserver($service);
        $observer->updated($siswa);
    }

    public function test_activates_account_when_status_changes_to_aktif(): void
    {
        $service = Mockery::mock(AkunSiswaService::class);
        $siswa = $this->createSiswaWithDirtyStatus('Aktif', 'Lulus');

        $service->shouldReceive('activateAccount')->once()->with($siswa);

        $observer = new SiswaObserver($service);
        $observer->updated($siswa);
    }

    public function test_does_nothing_when_status_not_changed(): void
    {
        $service = Mockery::mock(AkunSiswaService::class);
        $siswa = $this->createSiswaWithoutDirtyStatus('Aktif');

        $service->shouldNotReceive('deactivateAccount');
        $service->shouldNotReceive('activateAccount');

        $observer = new SiswaObserver($service);
        $observer->updated($siswa);

        $this->assertFalse($siswa->isDirty('status'));
    }

    public function test_does_nothing_when_status_changes_to_unrecognized_value(): void
    {
        $service = Mockery::mock(AkunSiswaService::class);
        $siswa = $this->createSiswaWithDirtyStatus('Cuti');

        $service->shouldNotReceive('deactivateAccount');
        $service->shouldNotReceive('activateAccount');

        $observer = new SiswaObserver($service);
        $observer->updated($siswa);

        $this->assertEquals('Cuti', $siswa->status);
    }
}
