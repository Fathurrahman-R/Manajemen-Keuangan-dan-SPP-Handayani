<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    /** @use HasFactory<\Database\Factories\AppSettingFactory> */
    use HasFactory;
    protected $table = 'app_settings';
    protected $fillable = [
        'nama_sekolah',
        'lokasi',
        'alamat',
        'email',
        'telepon',
        'kepala_sekolah',
        'bendahara',
        'kode_pos',
        'logo'
    ];
}
