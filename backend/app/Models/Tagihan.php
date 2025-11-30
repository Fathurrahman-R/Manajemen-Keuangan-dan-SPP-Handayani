<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagihan extends Model
{
    /** @use HasFactory<\Database\Factories\TagihanFactory> */
    use HasFactory;

    protected $table = 'tagihans';
    protected $primaryKey = 'kode_tagihan';
    public $incrementing = false;
    public $timestamps = true;
    protected $fillable = [
        'kode_tagihan',
        'jenis_tagihan_id',
        'nis',
        'tmp',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'tmp' => 'float',
        ];
    }

    public function jenis_tagihan()
    {
        return $this->belongsTo(JenisTagihan::class, 'jenis_tagihan_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'nis','nis');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'kode_tagihan','kode_tagihan');
    }
}

