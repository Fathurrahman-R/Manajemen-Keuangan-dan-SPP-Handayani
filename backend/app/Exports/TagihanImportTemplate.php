<?php

namespace App\Exports;

use App\Models\JenisTagihan;
use App\Models\TahunAjaran;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TagihanImportTemplate implements WithMultipleSheets
{
    private array $jenisTagihanNames = [];

    public function __construct(private int $branchId)
    {
        $periodeAktif = TahunAjaran::getAktif($branchId);

        if ($periodeAktif) {
            $this->jenisTagihanNames = JenisTagihan::where('branch_id', $branchId)
                ->where('tahun_ajaran_id', $periodeAktif->id)
                ->pluck('nama')
                ->toArray();
        }
    }

    public function sheets(): array
    {
        return [
            'Data Import' => new TagihanImportMainSheet($this->jenisTagihanNames),
            'Referensi' => new TagihanImportReferenceSheet($this->jenisTagihanNames),
        ];
    }
}
