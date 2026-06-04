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
        'batch_reference',
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
    public function siswaKelas()
    {
        return $this->hasMany(SiswaKelas::class, 'siswa_id');
    }
    public function branch()
    {
        return $this->BelongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'siswa_id');
    }

    public function pembayaranForGroupedView()
    {
        return \App\Models\Pembayaran::query()
            ->select([
                'pembayarans.kode_pembayaran',
                'pembayarans.kode_tagihan',
                'pembayarans.tanggal',
                'pembayarans.metode',
                'pembayarans.jumlah',
                'pembayarans.pembayar',
                'jenis_tagihans.nama as jenis_tagihan_nama',
                'jenis_tagihans.jumlah as jenis_tagihan_jumlah',
            ])
            ->join('tagihans', 'tagihans.kode_tagihan', '=', 'pembayarans.kode_tagihan')
            ->join('jenis_tagihans', 'jenis_tagihans.id', '=', 'tagihans.jenis_tagihan_id')
            ->where('tagihans.nis', $this->nis)
            ->orderByDesc('pembayarans.tanggal')
            ->get();
    }
}
