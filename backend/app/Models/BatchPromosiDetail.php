<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchPromosiDetail extends Model
{
    use HasFactory;

    protected $table = 'batch_promosi_details';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'batch_id',
        'siswa_id',
        'action',
        'source_kelas_id',
        'target_kelas_id',
        'previous_status',
        'previous_jenjang',
    ];

    protected function casts(): array
    {
        return [
            'siswa_id' => 'int',
            'source_kelas_id' => 'int',
            'target_kelas_id' => 'int',
        ];
    }

    public function batch()
    {
        return $this->belongsTo(BatchPromosi::class, 'batch_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function sourceKelas()
    {
        return $this->belongsTo(Kelas::class, 'source_kelas_id');
    }

    public function targetKelas()
    {
        return $this->belongsTo(Kelas::class, 'target_kelas_id');
    }
}
