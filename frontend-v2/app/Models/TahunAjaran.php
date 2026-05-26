<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajarans';
    protected $fillable = ['nama', 'tanggal_mulai', 'tanggal_selesai', 'status', 'branch_id'];

    public static function getAktif(int $branchId): ?self
    {
        return static::where('branch_id', $branchId)->where('status', 'Aktif')->first();
    }
}
