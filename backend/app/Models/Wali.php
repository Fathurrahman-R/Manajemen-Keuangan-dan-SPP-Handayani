<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wali extends Model
{
    use HasFactory;
    protected $table = 'walis';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $timestamps = true;
    public $incrementing = true;
    protected $fillable = [
        'nama',
        'jenis_kelamin',
        'agama',
        'pendidikan_terakhir',
        'pekerjaan',
        'alamat',
        'no_hp',
        'ket',
    ];

    public function siswa()
    {
        return $this->hasMany(Siswa::class, 'ayah_id')
            ->orWhere('ibu_id', $this->id)
            ->orWhere('wali_id', $this->id);
    }
}
