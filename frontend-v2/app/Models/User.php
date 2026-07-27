<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Lokal User model untuk frontend-v2.
 *
 * frontend-v2 TIDAK mengelola data user secara mandiri — sumber kebenaran ada
 * di backend (`backend/app/Models/User.php`). Tabel `users` di DB lokal hanya
 * dipakai oleh Filament untuk menyimpan referensi user yang sudah login
 * (Filament::auth()->loginUsingId) sehingga user menu, profile dropdown, dsb.
 * dapat resolve nama user.
 *
 * Jangan tulis logika domain (permission, branch rules, dsb.) di sini. Semua
 * permission/role/branch dibaca dari session (`data.permissions`, `data.roles`,
 * `data.branch_id`) yang diisi oleh respons login dari backend.
 */
class User extends Authenticatable implements FilamentUser
{
    use Notifiable;

    protected $fillable = [
        'username',
        'name',
        'password',
        'branch_id',
    ];

    protected $hidden = [
        'password',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'portal') {
            return \App\Helpers\PermissionHelper::hasResource('portal.billing');
        }

        // Default admin panel (empty ID)
        return \App\Helpers\PermissionHelper::hasResource('dashboard');
    }

    public function getFilamentName(): string
    {
        return $this->name ?? $this->username ?? '';
    }
}
