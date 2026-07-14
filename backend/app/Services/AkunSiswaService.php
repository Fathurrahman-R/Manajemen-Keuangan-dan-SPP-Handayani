<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AkunSiswaService
{
    /**
     * Create account for a single siswa.
     *
     * @return User|null Returns null if account already exists in the same branch
     */
    public function createAccount(Siswa $siswa): ?User
    {
        $username = $siswa->nis;

        // Check for duplicate NIS in the same branch
        $existingUser = User::where('username', $username)
            ->where('branch_id', $siswa->branch_id)
            ->first();

        if ($existingUser) {
            Log::warning('Akun siswa sudah ada: NIS '.$username.' di branch '.$siswa->branch_id);

            return null;
        }

        // Generate password from tanggal_lahir (DDMMYYYY format)
        $password = $this->generateDefaultPassword($siswa->tanggal_lahir);

        // Create the user account
        $user = User::create([
            'username' => $username,
            'name' => $siswa->nama,
            'password' => Hash::make($password),
            'branch_id' => $siswa->branch_id,
            'siswa_id' => $siswa->id,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        // Assign "siswa" role via spatie
        $user->assignRole('siswa');

        return $user;
    }

    /**
     * Create accounts in bulk for multiple siswa.
     * Each siswa is processed independently (no wrapping transaction).
     * Partial success is acceptable.
     *
     * @return array{created: int, errors: array}
     */
    public function bulkCreateAccounts(\Illuminate\Database\Eloquent\Collection $siswaList): array
    {
        $created = 0;
        $errors = [];

        foreach ($siswaList as $siswa) {
            try {
                $user = $this->createAccount($siswa);
                if ($user) {
                    $created++;
                } else {
                    $errors[] = ['siswa_id' => $siswa->id, 'nis' => $siswa->nis, 'reason' => 'Akun sudah ada'];
                }
            } catch (\Throwable $e) {
                $errors[] = ['siswa_id' => $siswa->id, 'nis' => $siswa->nis, 'reason' => $e->getMessage()];
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * Reset password to default (tanggal_lahir DDMMYYYY).
     */
    public function resetPassword(User $user): void
    {
        $siswa = $user->siswa;
        if (! $siswa) {
            return;
        }

        $password = $this->generateDefaultPassword($siswa->tanggal_lahir);
        $user->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);
    }

    /**
     * Deactivate account for a siswa.
     */
    public function deactivateAccount(Siswa $siswa): void
    {
        $user = User::where('siswa_id', $siswa->id)->first();
        if ($user) {
            $user->update(['is_active' => false]);
        }
    }

    /**
     * Activate account for a siswa.
     */
    public function activateAccount(Siswa $siswa): void
    {
        $user = User::where('siswa_id', $siswa->id)->first();
        if ($user) {
            $user->update(['is_active' => true]);
        }
    }

    /**
     * Generate default password from tanggal_lahir.
     *
     * @param  string  $tanggalLahir  Date in Y-m-d format (e.g., "2010-05-15")
     * @return string Password in DDMMYYYY format (e.g., "15052010")
     */
    public function generateDefaultPassword(string $tanggalLahir): string
    {
        return Carbon::parse($tanggalLahir)->format('dmY');
    }
}
