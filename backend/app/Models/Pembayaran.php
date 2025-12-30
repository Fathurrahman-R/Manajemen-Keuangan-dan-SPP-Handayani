<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    /** @use HasFactory<\Database\Factories\PembayaranFactory> */
    use HasFactory;

    protected $table = 'pembayarans';
    protected $primaryKey = 'kode_pembayaran';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    protected $fillable = [
        'kode_pembayaran',
        'kode_tagihan',
        'tanggal',
        'metode',
        'jumlah',
        'pembayar',
        'branch_id',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'float',
            'branch_id' => 'int',
        ];
    }

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'kode_tagihan', 'kode_tagihan');
    }
    public function branch()
    {
        return $this->BelongsTo(Branch::class, 'branch_id');
    }
}
