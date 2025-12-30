<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswas';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    protected $fillable = [
        'nis',
        'nisn',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'ayah_id',
        'ibu_id',
        'wali_id',
        'jenjang',
        'kelas_id',
        'kategori_id',
        'asal_sekolah',
        'kelas_diterima',
        'tahun_diterima',
        'status',
        'keterangan',
        'branch_id',
    ];
    protected $casts = [
        'id' => 'integer',
        'ayah_id' => 'integer',
        'ibu_id' => 'integer',
        'wali_id' => 'integer',
        'kelas_id' => 'integer',
        'kategori_id' => 'integer',
        'branch_id' => 'integer',
    ];

    public function ayah(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Ayah::class);
    }
    public function ibu(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Ibu::class);
    }
    public function wali(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Wali::class);
    }
    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }
    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }
    public function tagihan()
    {
        return $this->hasMany(Tagihan::class,'nis','nis');
    }
    public function branch()
    {
        return $this->BelongsTo(Branch::class, 'branch_id');
    }

}
