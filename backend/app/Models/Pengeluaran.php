<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    /** @use HasFactory<\Database\Factories\PengeluaranFactory> */
    use HasFactory;

    protected $table = 'pengeluarans';
    protected $fillable = [
        'tanggal',
        'uraian',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'float',
        ];
    }
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = true;
}
