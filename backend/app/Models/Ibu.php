<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ibu extends Model
{
    /** @use HasFactory<\Database\Factories\IbuFactory> */
    use HasFactory;

    protected $table = 'ibu'; // diperbaiki dari 'ayah'
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
