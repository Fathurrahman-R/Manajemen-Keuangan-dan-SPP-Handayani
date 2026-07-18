<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission;

class PermissionEndpoint extends Model
{
    protected $fillable = [
        'permission_id',
        'resource_key',
        'group',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
