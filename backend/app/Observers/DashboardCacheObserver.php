<?php

namespace App\Observers;

use App\Models\Pengeluaran;
use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Model;

class DashboardCacheObserver
{
    public function created(Model $model): void
    {
        $this->invalidate($model);
    }

    public function updated(Model $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        $branchId = $model->branch_id;

        if (! $branchId) {
            return;
        }

        if ($model instanceof Pengeluaran) {
            DashboardService::invalidateKasCache($branchId);
        }

        DashboardService::invalidateCache($branchId);
    }
}
