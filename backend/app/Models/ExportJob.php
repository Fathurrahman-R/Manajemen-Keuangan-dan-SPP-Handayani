<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class ExportJob extends Model
{
    protected $table = 'export_jobs';

    protected $fillable = [
        'job_reference',
        'user_id',
        'export_type',
        'filters',
        'format',
        'status',
        'file_path',
        'error_message',
        'expires_at',
        'branch_id',
    ];

    protected $casts = [
        'filters' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Generate a temporary signed URL for downloading the export file.
     *
     * Returns null if no file_path is set or the job is not completed.
     */
    public function getSignedUrl(): ?string
    {
        if (! $this->file_path || $this->status !== 'completed') {
            return null;
        }

        return URL::temporarySignedRoute(
            'export.download',
            $this->expires_at,
            ['path' => $this->file_path]
        );
    }
}
