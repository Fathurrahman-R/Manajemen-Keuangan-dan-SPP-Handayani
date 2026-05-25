<?php

namespace App\Observers;

use App\Models\Siswa;
use App\Services\AkunSiswaService;

class SiswaObserver
{
    public function __construct(protected AkunSiswaService $akunSiswaService) {}

    public function updated(Siswa $siswa): void
    {
        // Only react if status field was changed
        if (!$siswa->isDirty('status')) {
            return;
        }

        $newStatus = $siswa->status;

        if (in_array($newStatus, ['Lulus', 'Pindah', 'Keluar'])) {
            $this->akunSiswaService->deactivateAccount($siswa);
        } elseif ($newStatus === 'Aktif') {
            $this->akunSiswaService->activateAccount($siswa);
        }
    }
}
