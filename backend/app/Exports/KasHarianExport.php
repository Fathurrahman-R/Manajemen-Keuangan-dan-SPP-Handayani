<?php

namespace App\Exports;

use App\Exports\Sheets\KasHarianPemasukanSheet;
use App\Exports\Sheets\KasHarianPengeluaranSheet;
use App\Exports\Sheets\KasHarianRingkasanSheet;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KasHarianExport implements WithMultipleSheets
{
    public function __construct(
        private Builder $pemasukanQuery,
        private Builder $pengeluaranQuery,
        private int $bulan,
        private int $tahun,
    ) {}

    public function sheets(): array
    {
        return [
            'Ringkasan' => new KasHarianRingkasanSheet($this->pemasukanQuery, $this->pengeluaranQuery, $this->bulan, $this->tahun),
            'Pemasukan' => new KasHarianPemasukanSheet($this->pemasukanQuery),
            'Pengeluaran' => new KasHarianPengeluaranSheet($this->pengeluaranQuery),
        ];
    }
}
