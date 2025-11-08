<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wali extends Model
{
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
}
