<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email',
        'token',
        'used',
        'created_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'used' => 'boolean',
            'created_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->used && ! $this->isExpired();
    }
}
