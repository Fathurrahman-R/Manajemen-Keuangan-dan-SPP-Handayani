<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionEndpoint extends Model
{
    protected $table = 'permission_endpoints';

    protected $guarded = [];

    public function permission()
    {
        return $this->belongsTo(\App\Models\Permission::class);
    }
}
