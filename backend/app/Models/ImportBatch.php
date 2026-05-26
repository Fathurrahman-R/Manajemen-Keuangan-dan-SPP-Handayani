<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    use HasFactory;

    protected $table = 'import_batches';

    protected $fillable = [
        'batch_reference',
        'user_id',
        'import_type',
        'file_name',
        'total_rows',
        'success_count',
        'error_count',
        'status',
        'error_message',
        'rolled_back_at',
        'rolled_back_by',
        'branch_id',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'success_count' => 'integer',
        'error_count' => 'integer',
        'rolled_back_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rolledBackByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Determine if this batch is eligible for rollback.
     * A batch is eligible if its status is 'completed' and it was created within the last 48 hours.
     */
    public function isRollbackEligible(): bool
    {
        return $this->status === 'completed'
            && $this->created_at->diffInHours(now()) < 48;
    }
}
