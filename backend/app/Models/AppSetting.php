<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    /** @use HasFactory<\Database\Factories\AppSettingFactory> */
    use HasFactory;
    protected $table = 'app_settings';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
    protected $fillable = [
        'nama_sekolah',
        'lokasi',
        'alamat',
        'email',
        'telepon',
        'kepala_sekolah',
        'bendahara',
        'kode_pos',
        'logo',
        'branch_id'
    ];
    protected $casts = [
        'id' => 'integer',
        'branch_id' => 'int',
    ];
    public function branch()
    {
        return $this->BelongsTo(Branch::class, 'branch_id');
    }
}
