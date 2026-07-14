<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahunAjaran extends Model
{
    use HasFactory;

    protected $table = 'tahun_ajarans';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'branch_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'branch_id' => 'int',
        ];
    }

    /**
     * Get the active TahunAjaran for a given branch.
     */
    public static function getAktif(int $branchId): ?self
    {
        return static::where('branch_id', $branchId)
            ->where('status', 'Aktif')
            ->first();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function tagihans()
    {
        return $this->hasMany(Tagihan::class, 'tahun_ajaran_id');
    }

    public function jenisTagihans()
    {
        return $this->hasMany(JenisTagihan::class, 'tahun_ajaran_id');
    }

    public function siswaKelas()
    {
        return $this->hasMany(SiswaKelas::class, 'tahun_ajaran_id');
    }
}
