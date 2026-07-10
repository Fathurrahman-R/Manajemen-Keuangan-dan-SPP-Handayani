<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission;

class PagePermission extends Model
{
    protected $fillable = [
        'resource_key',
        'permission_name',
        'guard_name',
        'group',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_name', 'name');
    }
}
