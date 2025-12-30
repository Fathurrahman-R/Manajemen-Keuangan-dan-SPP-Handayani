<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;
    protected $table = 'kategoris';
    protected $primaryKey = 'id';
    protected $fillable = ['nama','branch_id'];
    protected $keyType = 'int';
    public $timestamps = true;
    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'branch_id'=>'int'
        ];
    }

    public function siswa()
    {
        return $this->hasMany(Siswa::class);
    }
    public function branch()
    {
        return $this->BelongsTo(Branch::class, 'branch_id');
    }
}
