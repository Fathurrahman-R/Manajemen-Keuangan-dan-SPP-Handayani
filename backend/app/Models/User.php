<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles;

    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $timestamps = true;

    public $incrementing = true;

    protected $fillable = [
        'username',
        'name',
        'email',
        'email_verified_at',
        'password',
        'branch_id',
        'siswa_id',
        'is_active',
        'must_change_password',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'id' => 'int',
            'branch_id' => 'int',
            'siswa_id' => 'int',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'email_verified_at' => 'datetime',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Normalize email to lowercase on set.
     */
    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value !== null
            ? strtolower(trim($value))
            : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getBranchId()
    {
        return $this->branch_id;
    }
}
