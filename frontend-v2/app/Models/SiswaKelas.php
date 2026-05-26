<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiswaKelas extends Model
{
    protected $table = 'siswa_kelas';
    protected $fillable = ['siswa_id', 'kelas_id', 'tahun_ajaran_id'];
}
