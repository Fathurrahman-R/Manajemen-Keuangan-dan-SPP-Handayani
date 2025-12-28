<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $table = 'branches';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'location'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'branch_id');
    }
    public function siswas()
    {
        return $this->hasMany(Siswa::class, 'branch_id');
    }
    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'branch_id');
    }
    public function kategoris()
    {
        return $this->hasMany(Kategori::class, 'branch_id');
    }
    public function jenis_tagihans()
    {
        return $this->hasMany(JenisTagihan::class, 'branch_id');
    }
    public function tagihans()
    {
        return $this->hasMany(Tagihan::class, 'branch_id');
    }
    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class, 'branch_id');
    }
    public function pengeluarans()
    {
        return $this->hasMany(Pengeluaran::class, 'branch_id');
    }
    public function app_settings()
    {
        return $this->hasMany(AppSetting::class, 'branch_id');
    }

}
