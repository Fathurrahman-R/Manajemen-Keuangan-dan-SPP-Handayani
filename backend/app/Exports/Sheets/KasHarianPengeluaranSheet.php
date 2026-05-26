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
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Uraian',
            'Jumlah',
        ];
    }

    /**
     * @param \App\Models\Pengeluaran $pengeluaran
     */
    public function map($pengeluaran): array
    {
        return [
            $pengeluaran->tanggal,
            $pengeluaran->uraian,
            $pengeluaran->jumlah,
        ];
    }
}
