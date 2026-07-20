<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PengeluaranRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uraian',
        'jumlah',
        'tanggal_kebutuhan',
        'kategori_pengeluaran',
        'lampiran',
        'status',
        'requester_id',
        'branch_id',
    ];

    protected $appends = ['lampiran_url'];

    protected function lampiranUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->lampiran ? Storage::disk('public')->url($this->lampiran) : null);
    }

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'tanggal_kebutuhan' => 'date',
            'branch_id' => 'int',
            'requester_id' => 'int',
        ];
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function approvalLogs()
    {
        return $this->hasMany(ApprovalLog::class)->orderBy('created_at');
    }

    public function pengeluaran()
    {
        return $this->hasOne(Pengeluaran::class, 'pengeluaran_request_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function isDeletable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }
}
