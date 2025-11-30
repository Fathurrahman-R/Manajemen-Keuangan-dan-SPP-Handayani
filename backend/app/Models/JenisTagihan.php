<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisTagihan extends Model
{
    /** @use HasFactory<\Database\Factories\JenisTagihanFactory> */
    use HasFactory;

    protected $table = 'jenis_tagihans';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = true;
    protected $fillable = [
        'nama',
        'jatuh_tempo',
        'jumlah'
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'float',
        ];
    }

    public function tagihan()
    {
        return $this->hasMany(Tagihan::class,'jenis_tagihan_id','id');
    }

}
