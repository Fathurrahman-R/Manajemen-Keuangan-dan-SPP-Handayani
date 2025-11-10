<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswas';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    protected $fillable = [
        'nis',
        'nisn',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'ayah',
        'ibu',
        'wali',
        'jenjang',
        'kelas',
        'kategori',
        'asal_sekolah',
        'kelas_diterima',
        'tahun_diterima',
        'status',
        'keterangan',
    ];
}
