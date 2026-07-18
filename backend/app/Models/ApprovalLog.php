<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'pengeluaran_request_id',
        'previous_status',
        'new_status',
        'user_id',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function pengeluaranRequest()
    {
        return $this->belongsTo(PengeluaranRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
