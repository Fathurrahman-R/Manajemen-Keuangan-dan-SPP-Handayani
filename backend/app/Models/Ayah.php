<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ayah extends Model
{
    /** @use HasFactory<\Database\Factories\AyahFactory> */
    use HasFactory;

    protected $table = 'ayah';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = true;
    protected $fillable = [
        'nama',
        'pendidikan',
        'pekerjaan',
    ];
    protected $casts = [
        'id' => 'integer'
    ];

    public function siswa()
    {
        return $this->hasMany(Siswa::class);
    }
}
