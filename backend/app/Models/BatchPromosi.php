<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchPromosi extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_promosis';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'batch_type',
        'source_tahun_ajaran_id',
        'target_tahun_ajaran_id',
        'kelas_id',
        'processed_by',
        'processed_at',
        'status',
        'branch_id',
    ];

    protected function casts(): array
    {
        return [
            'source_tahun_ajaran_id' => 'int',
            'target_tahun_ajaran_id' => 'int',
            'kelas_id' => 'int',
            'processed_by' => 'int',
            'processed_at' => 'datetime',
            'branch_id' => 'int',
        ];
    }

    public function details()
    {
        return $this->hasMany(BatchPromosiDetail::class, 'batch_id');
    }

    public function sourceTahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'source_tahun_ajaran_id');
    }

    public function targetTahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'target_tahun_ajaran_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
