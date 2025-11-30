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
        'keterangan',
    ];
    protected $casts = [
        'id' => 'integer'
    ];

    public function siswa()
    {
        // relasi utama siswa sebagai wali
        return $this->hasMany(Siswa::class,'wali_id','id');
    }
}
