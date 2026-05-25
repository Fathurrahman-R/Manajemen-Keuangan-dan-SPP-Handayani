<?php

namespace App\Services\Notifications;

use App\Models\Siswa;

class RecipientResolver
{
    /**
     * Resolve email recipient for a Siswa.
     * Priority: Wali → Ibu → Ayah (first non-null email)
     * Returns null if no email found.
     */
    public function resolve(Siswa $siswa): ?string
    {
        if ($siswa->wali && !empty($siswa->wali->email)) {
            return $siswa->wali->email;
        }
        if ($siswa->ibu && !empty($siswa->ibu->email)) {
            return $siswa->ibu->email;
        }
        if ($siswa->ayah && !empty($siswa->ayah->email)) {
            return $siswa->ayah->email;
        }
        return null;
    }
}
