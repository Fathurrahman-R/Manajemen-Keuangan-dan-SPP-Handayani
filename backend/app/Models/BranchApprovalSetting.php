<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchApprovalSetting extends Model
{
        protected $fillable = [
        'branch_id',
        'auto_approval_enabled',
        'auto_approval_threshold',
    ];

    protected function casts(): array
    {
        return [
            'branch_id' => 'int',
            'auto_approval_enabled' => 'boolean',
            'auto_approval_threshold' => 'decimal:2',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
