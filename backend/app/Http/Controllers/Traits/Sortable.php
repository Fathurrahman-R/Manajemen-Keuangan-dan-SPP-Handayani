<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Trait Sortable
 *
 * Provides generic sort logic for controller index endpoints.
 * Reads `sort` and `direction` query parameters and applies orderBy to the query.
 *
 * Usage in controller:
 *   $query = $this->applySorting($query, ['nama', 'tanggal', 'jumlah']);
 */
trait Sortable
{
    /**
     * Apply sorting to an Eloquent query based on request parameters.
     *
     * @param Builder $query The Eloquent query builder instance
     * @param array $allowedColumns Whitelist of columns that can be sorted
     * @param string $defaultColumn Default sort column if none specified
     * @param string $defaultDirection Default sort direction if none specified
     * @return Builder
     */
    protected function applySorting(
        Builder $query,
        array $allowedColumns,
        string $defaultColumn = 'id',
        string $defaultDirection = 'asc'
    ): Builder {
        $sortColumn = request('sort');
        $sortDirection = strtolower(request('direction', $defaultDirection));

        // Validate direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = $defaultDirection;
        }

        // Only apply if column is in the whitelist
        if ($sortColumn && in_array($sortColumn, $allowedColumns)) {
            // Remove any existing orders to avoid conflicts
            $query->reorder($sortColumn, $sortDirection);
        }

        return $query;
    }
}
