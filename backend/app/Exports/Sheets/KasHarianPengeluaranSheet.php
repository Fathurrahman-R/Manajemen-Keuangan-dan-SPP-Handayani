<?php

namespace App\Exports\Sheets;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class KasHarianPengeluaranSheet implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private Builder $query,
    ) {}

    public function title(): string
    {
        return 'Pengeluaran';
    }

    public function query(): Builder
    {
        return $this->query->with(['pengeluaranRequest.requester', 'pengeluaranRequest.approvalLogs.user']);
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Pengeluaran',
            'Jumlah',
            'Pengaju',
            'Penyetuju',
        ];
    }

    /**
     * @param  \App\Models\Pengeluaran  $pengeluaran
     */
    public function map($pengeluaran): array
    {
        return [
            $pengeluaran->tanggal,
            $pengeluaran->uraian,
            $pengeluaran->jumlah,
            $pengeluaran->pengaju_name,
            $pengeluaran->penyetuju_name,
        ];
    }
}
