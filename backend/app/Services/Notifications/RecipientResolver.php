<?php

namespace App\Services\Notifications;

use App\Models\Siswa;

class RecipientResolver
{
    /**
     * Resolve email recipient for a Siswa.
     *
     * Priority:
     *   1. Email dari akun User yang terhubung ke siswa (users.email via siswa_id)
     *   2. Email Wali
     *   3. Email Ibu
     *   4. Email Ayah
     *
     * Returns null if no email found in any of the above.
     */
    public function resolve(Siswa $siswa): ?string
    {
        // 1. Email dari akun siswa (tabel users, kolom email)
        $siswa->loadMissing('user');
        if ($siswa->user && ! empty($siswa->user->email)) {
            return $siswa->user->email;
        }

        // 2. Email Wali
        if ($siswa->wali && ! empty($siswa->wali->email)) {
            return $siswa->wali->email;
        }

        // 3. Email Ibu
        if ($siswa->ibu && ! empty($siswa->ibu->email)) {
            return $siswa->ibu->email;
        }

        // 4. Email Ayah
        if ($siswa->ayah && ! empty($siswa->ayah->email)) {
            return $siswa->ayah->email;
        }

        return null;
    }
}
