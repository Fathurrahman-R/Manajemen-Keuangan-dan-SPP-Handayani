<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

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
        return true;
    }

    public function getFilamentName(): string
    {
        return $this->name ?? $this->username ?? '';
    }

    /**
     * Override notifications relationship to use the filament_notifications table
     * (the backend already has a `notifications` table with a different schema).
     */
    public function notifications()
    {
        return $this->morphMany(FilamentDatabaseNotification::class, 'notifiable')
            ->orderBy('created_at', 'desc');
    }
}
