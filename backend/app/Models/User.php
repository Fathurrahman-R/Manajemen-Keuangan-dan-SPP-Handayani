<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements Authenticatable
{
    use HasFactory;
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $timestamps = true;
    public $incrementing = true;
    protected $fillable = [
        'username',
        'password',
        'branch_id',
    ];

    protected function casts(): array
    {
        return [
            'branch_id'=> 'int'
        ];
    }

    public function branch()
    {
        return $this->BelongsTo(Branch::class, 'branch_id');
    }

    public function getBranchId()
    {
        return $this->branch_id;
    }

    public function getAuthIdentifierName()
    {
        return 'username';
    }

    public function getAuthIdentifier()
    {
        return $this->username;
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        $this->token;
    }

    public function setRememberToken($value)
    {
        return $this->token($value);
    }

    public function getRememberTokenName()
    {
        return 'token';
    }
}
