<?php

namespace App\Services;

use App\Models\Siswa;
use Illuminate\Database\Eloquent\Collection;

class SiblingDetectionService
{
    /**
     * Find all siblings of a siswa within the same branch.
     *
     * Siblings are other Siswa records that share at least one non-null
     * parent ID (ayah_id, ibu_id, or wali_id) with the input siswa.
     *
     * @return Collection<int, Siswa>
     */
    public function findSiblings(Siswa $siswa): Collection
    {
        // If the siswa has no parent IDs at all, there can be no siblings
        if (is_null($siswa->ayah_id) && is_null($siswa->ibu_id) && is_null($siswa->wali_id)) {
            return new Collection;
        }

        return Siswa::where('branch_id', $siswa->branch_id)
            ->where('id', '!=', $siswa->id)
            ->where(function ($query) use ($siswa) {
                if (! is_null($siswa->ayah_id)) {
                    $query->orWhere('ayah_id', $siswa->ayah_id);
                }
                if (! is_null($siswa->ibu_id)) {
                    $query->orWhere('ibu_id', $siswa->ibu_id);
                }
                if (! is_null($siswa->wali_id)) {
                    $query->orWhere('wali_id', $siswa->wali_id);
                }
            })
            ->get();
    }
}
