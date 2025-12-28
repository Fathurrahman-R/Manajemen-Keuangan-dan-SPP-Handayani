<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;
    protected $table = 'kelas';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = true;
    protected $fillable = ['jenjang','nama'];

    public function siswa()
    {
        return $this->hasMany(Siswa::class);
    }
    public function branch()
    {
        return $this->BelongsTo(Branch::class, 'branch_id');
    }
}
