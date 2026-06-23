<?php

namespace App\Exports\Sheets;

use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class RekapBulananRingkasanSheet implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    public function __construct(
        private array $summary,
        private int $tahun,
    ) {}

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function headings(): array
    {
        return [
            'Bulan',
            'Total Pemasukan',
            'Total Pengeluaran',
            'Saldo',
            'Catatan',
        ];
    }

    public function collection()
    {
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $branchId = Auth::user()?->branch_id;

        $rows = collect();
        $totalMasuk = 0;
        $totalKeluar = 0;

        foreach ($this->summary as $item) {
            $bulan = (int) ($item['bulan'] ?? 0);

            $catatan = $this->buildCatatan($branchId, $bulan, $this->tahun);

            $masuk = (int) ($item['total_pemasukan'] ?? 0);
            $keluar = (int) ($item['total_pengeluaran'] ?? 0);
            $saldo = (int) ($item['saldo'] ?? ($masuk - $keluar));

            $totalMasuk += $masuk;
            $totalKeluar += $keluar;

            $rows->push([
                $namaBulan[$bulan] ?? $bulan,
                $masuk,
                $keluar,
                $saldo,
                $catatan,
            ]);
        }

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
     * Build a per-month "Catatan" column listing every transaction in that
     * month, branch-scoped, so the rekap export carries the same level of
     * detail as the kas harian one (per the issue report).
     */
    private function buildCatatan(?int $branchId, int $bulan, int $tahun): string
    {
        if (! $branchId) {
            return '';
        }

        $lines = [];

        $pemasukan = Pembayaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->with(['tagihan.siswa:id,nis,nama', 'tagihan.jenis_tagihan:id,nama'])
            ->orderBy('tanggal')
            ->get();

        foreach ($pemasukan as $p) {
            $lines[] = sprintf(
                '%s · Pemasukan — %s (%s) Rp %s',
                (string) $p->tanggal,
                $p->tagihan?->siswa?->nama ?? '-',
                $p->tagihan?->jenis_tagihan?->nama ?? 'Pembayaran',
                number_format((int) $p->jumlah, 0, ',', '.'),
            );
        }

        $pengeluaran = Pengeluaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->with(['pengeluaranRequest.requester:id,name', 'pengeluaranRequest.approvalLogs.user:id,name'])
            ->orderBy('tanggal')
            ->get();

        foreach ($pengeluaran as $e) {
            $lines[] = sprintf(
                '%s · Pengeluaran — %s Rp %s',
                (string) $e->tanggal,
                $e->uraian ?? '-',
                number_format((int) $e->jumlah, 0, ',', '.'),
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Enable wrap-text + top alignment on the "Catatan" column so the per-month
     * breakdown stays readable inside Excel.
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
