<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Lightweight, non-persisted Eloquent model used to wrap array records fetched
 * from the backend API, so Filament table features that require a real
 * `Model` instance (e.g. `->groups()`) work with API-backed (non-Eloquent)
 * table data sources.
 */
class ApiTableRecord extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public $incrementing = false;
}
