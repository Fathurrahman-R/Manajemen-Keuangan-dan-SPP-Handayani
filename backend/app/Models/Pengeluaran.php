<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    /** @use HasFactory<\Database\Factories\PengeluaranFactory> */
    use HasFactory;

    protected $table = 'pengeluarans';
    protected $fillable = [
        'tanggal',
        'uraian',
        'jumlah',
        'branch_id',
        'pengeluaran_request_id',
    ];
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'jumlah' => 'float',
            'branch_id' => 'int',
        ];
    }
    public function branch()
    {
        return $this->BelongsTo(Branch::class, 'branch_id');
    }

    public function pengeluaranRequest()
    {
        return $this->belongsTo(\App\Models\PengeluaranRequest::class, 'pengeluaran_request_id');
    }

    /**
     * Display name of who submitted (pengaju).
     */
    public function getPengajuNameAttribute(): ?string
    {
        return $this->pengeluaranRequest?->requester?->name;
    }

    /**
     * Display name of who approved (penyetuju).
     *
     * Picks the user from the latest ApprovalLog whose `new_status = 'approved'`.
     * Returns null when the underlying request has no approval log (e.g.
     * legacy direct-entry pengeluaran).
     */
    public function getPenyetujuNameAttribute(): ?string
    {
        $req = $this->pengeluaranRequest;
        if (! $req) {
            return null;
        }

        // approvalLogs() is ordered ASC by created_at; reverse to find latest approve.
        $log = $req->approvalLogs()
            ->where('new_status', 'approved')
            ->latest('created_at')
            ->first();

        return $log?->user?->name;
    }
}
