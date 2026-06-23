<?php

namespace App\Exports\Sheets;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class KasHarianRingkasanSheet implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    public function __construct(
        private Builder $pemasukanQuery,
        private Builder $pengeluaranQuery,
        private int $bulan,
        private int $tahun,
    ) {}

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Total Pemasukan',
            'Total Pengeluaran',
            'Saldo',
            'Keterangan',
        ];
    }

    public function collection()
    {
        // Pull all underlying transactions, eager-load enough to render
        // human-readable line items in the "Keterangan" column.
        $pemasukan = (clone $this->pemasukanQuery)
            ->with(['tagihan.siswa:id,nis,nama', 'tagihan.jenis_tagihan:id,nama'])
            ->get();
        $pengeluaran = (clone $this->pengeluaranQuery)
            ->with(['pengeluaranRequest.requester:id,name', 'pengeluaranRequest.approvalLogs.user:id,name'])
            ->get();

        // Group both streams by date (YYYY-MM-DD) so each row in the sheet is
        // a single day with a running breakdown of what happened that day.
        $byTanggal = collect();

        foreach ($pemasukan as $p) {
            $tgl = (string) $p->tanggal;
            $byTanggal[$tgl] = $byTanggal[$tgl] ?? ['masuk' => 0, 'keluar' => 0, 'lines' => []];
            $byTanggal[$tgl]['masuk'] += (int) $p->jumlah;
            $nama = $p->tagihan?->siswa?->nama ?? '-';
            $jenis = $p->tagihan?->jenis_tagihan?->nama ?? 'Pembayaran';
            $byTanggal[$tgl]['lines'][] = sprintf(
                'Pemasukan — %s (%s) Rp %s',
                $nama,
                $jenis,
                number_format((int) $p->jumlah, 0, ',', '.'),
            );
        }

        foreach ($pengeluaran as $e) {
            $tgl = (string) $e->tanggal;
            $byTanggal[$tgl] = $byTanggal[$tgl] ?? ['masuk' => 0, 'keluar' => 0, 'lines' => []];
            $byTanggal[$tgl]['keluar'] += (int) $e->jumlah;
            $byTanggal[$tgl]['lines'][] = sprintf(
                'Pengeluaran — %s Rp %s',
                $e->uraian ?? '-',
                number_format((int) $e->jumlah, 0, ',', '.'),
            );
        }

        $rows = collect();
        $totalMasuk = 0;
        $totalKeluar = 0;

        foreach ($byTanggal->sortKeys() as $tgl => $data) {
            $totalMasuk += $data['masuk'];
            $totalKeluar += $data['keluar'];

            $rows->push([
                $tgl,
                $data['masuk'],
                $data['keluar'],
                $data['masuk'] - $data['keluar'],
                implode("\n", $data['lines']),
            ]);
        }

        // Footer total row.
        $rows->push([
            'TOTAL',
            $totalMasuk,
            $totalKeluar,
            $totalMasuk - $totalKeluar,
            '',
        ]);

        return $rows;
    }

    /**
     * Enable wrap-text + top alignment on the "Keterangan" column so the
     * per-day breakdown stays readable inside Excel.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highest = $sheet->getHighestRow();
                $sheet->getStyle('E1:E' . $highest)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical('top');
                $sheet->getColumnDimension('E')->setWidth(60);
            },
        ];
    }
}
