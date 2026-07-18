<?php

namespace App\Exports;

use App\Exports\Sheets\RekapBulananPemasukanSheet;
use App\Exports\Sheets\RekapBulananPengeluaranSheet;
use App\Exports\Sheets\RekapBulananRingkasanSheet;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RekapBulananExport implements WithMultipleSheets
{
    public function __construct(
        private array $summary,
        private Builder $pemasukanQuery,
        private Builder $pengeluaranQuery,
        private int $tahun,
    ) {}

    public function sheets(): array
    {
        return [
            'Ringkasan' => new RekapBulananRingkasanSheet($this->summary, $this->tahun),
            'Pemasukan' => new RekapBulananPemasukanSheet($this->pemasukanQuery),
            'Pengeluaran' => new RekapBulananPengeluaranSheet($this->pengeluaranQuery),
        ];
    }
}
