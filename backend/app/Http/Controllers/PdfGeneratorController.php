<?php

namespace App\Http\Controllers;

use App\Http\Resources\KasResource;
use App\Models\AppSetting;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Services\LaporanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PdfGeneratorController extends Controller
{
    #[HeaderParameter('Authorization')]
    public function get(string $kode_pembayaran)
    {
        $appSetting = AppSetting::query()->first();
        if (!$appSetting) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => [
                        'informasi sekolah tidak ditemukan! mohon untuk mengisi informasi sekolah terlebih dahulu.'
                    ]
                ]
            ],404));
        }
        // Ambil resource kwitansi sebagai array data sederhana
        $resource = PembayaranController::kwitansi($kode_pembayaran); // KwitansiResource
        $data = $resource->toArray(request());

        // Resolve logo absolute path from public disk; fallback to public favicon
        $logoRelative = $data['setting']['logo'] ?? null;
        $logo = null;
        if ($logoRelative && Storage::disk('public')->exists($logoRelative)) {
            // Absolute filesystem path DomPDF can read
            $logo = Storage::disk('public')->path($logoRelative);
        }
        if (!$logo) {
            $logo = public_path('favicon.ico');
        }

        // Gabungkan ke payload view (blade mengharapkan variabel terpisah)
        $viewData = [
            'kode_pembayaran' => $data['kode_pembayaran'],
            'setting'   => $data['setting'] ?? [],
            'tanggal'   => $data['tanggal'] ?? null,
            'pembayar'  => $data['pembayar'] ?? null,
            'jumlah'    => $data['jumlah'] ?? 0,
            'untuk'     => $data['untuk'] ?? '-',
            'sejumlah'  => $data['sejumlah'] ?? '-',
            'logo'      => $logo,
        ];

        $pdf = Pdf::loadView('kwitansi', $viewData)
        ->setPaper('A6', 'landscape');
        return $pdf->stream("kwitansi-{$kode_pembayaran}.pdf");
    }

    #[HeaderParameter('Authorization')]
    public function exportKas(Request $request)
    {
        $kas = app(LaporanService::class)->KasHarian($request)->toArray($request);

        // Build per-day "keterangan" map so the PDF mirrors the Excel
        // export's Ringkasan sheet: each row carries a human-readable list
        // of every transaction recorded on that date.
        $branchId = Auth::user()->branch_id;
        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;
        $bulanPad = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);
        $start = sprintf('%04d-%s-01', $tahun, $bulanPad);
        $end = date('Y-m-t', strtotime($start));

        $keteranganByTanggal = $this->buildKeteranganByTanggal($branchId, $start, $end);

        $viewData = [
            'rows'  => $kas,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'keterangan' => $keteranganByTanggal,
        ];
        $pdf = Pdf::loadView('Laporan.kas-harian', $viewData)
            ->setPaper('A4', 'landscape');
        return $pdf->stream("Kas harian {$request->bulan}.pdf");
    }

    #[HeaderParameter('Authorization')]
    public function exportRekapBulanan(Request $request)
    {
        $rekap = app(LaporanService::class)->RekapBulanan($request)->toArray($request);

        $branchId = Auth::user()->branch_id;
        $tahun = (int) $request->tahun;
        $catatanByBulan = $this->buildCatatanByBulan($branchId, $tahun);

        $viewData = [
            'rows'  => $rekap,
            'tahun' => $tahun,
            'catatan' => $catatanByBulan,
        ];
        $pdf = Pdf::loadView('Laporan.rekap-bulanan', $viewData)
            ->setPaper('A4', 'landscape');
        return $pdf->stream("Rekap Bulanan {$request->tahun}.pdf");
    }

    /**
     * Build per-date list of HTML-safe lines describing every pemasukan +
     * pengeluaran in the given window. Keyed by Indonesian-localised date
     * (matches the row 'tanggal' the LaporanService emits).
     *
     * @return array<string, list<string>>
     */
    private function buildKeteranganByTanggal(int $branchId, string $start, string $end): array
    {
        $map = [];

        $pemasukan = Pembayaran::query()
            ->where('branch_id', $branchId)
            ->whereBetween('tanggal', [$start, $end])
            ->with(['tagihan.siswa:id,nis,nama', 'tagihan.jenis_tagihan:id,nama'])
            ->orderBy('tanggal')
            ->get();

        foreach ($pemasukan as $p) {
            $key = \Carbon\Carbon::parse($p->tanggal)->locale('id')->translatedFormat('d F Y');
            $map[$key][] = sprintf(
                'Pemasukan — %s (%s) Rp %s',
                $p->tagihan?->siswa?->nama ?? '-',
                $p->tagihan?->jenis_tagihan?->nama ?? 'Pembayaran',
                number_format((int) $p->jumlah, 0, ',', '.'),
            );
        }

        $pengeluaran = Pengeluaran::query()
            ->where('branch_id', $branchId)
            ->whereBetween('tanggal', [$start, $end])
            ->with(['pengeluaranRequest.requester:id,name', 'pengeluaranRequest.approvalLogs.user:id,name'])
            ->orderBy('tanggal')
            ->get();

        foreach ($pengeluaran as $e) {
            $key = \Carbon\Carbon::parse($e->tanggal)->locale('id')->translatedFormat('d F Y');
            $map[$key][] = sprintf(
                'Pengeluaran — %s Rp %s',
                $e->uraian ?? '-',
                number_format((int) $e->jumlah, 0, ',', '.'),
            );
        }

        return $map;
    }

    /**
     * Build per-month list of HTML-safe lines for the rekap bulanan PDF.
     *
     * @return array<string, list<string>>
     */
    private function buildCatatanByBulan(int $branchId, int $tahun): array
    {
        $map = [];

        $pemasukan = Pembayaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->with(['tagihan.siswa:id,nis,nama', 'tagihan.jenis_tagihan:id,nama'])
            ->orderBy('tanggal')
            ->get();

        foreach ($pemasukan as $p) {
            $key = \Carbon\Carbon::parse($p->tanggal)->locale('id')->translatedFormat('F');
            $map[$key][] = sprintf(
                '%s · Pemasukan — %s (%s) Rp %s',
                \Carbon\Carbon::parse($p->tanggal)->format('d/m'),
                $p->tagihan?->siswa?->nama ?? '-',
                $p->tagihan?->jenis_tagihan?->nama ?? 'Pembayaran',
                number_format((int) $p->jumlah, 0, ',', '.'),
            );
        }

        $pengeluaran = Pengeluaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->with(['pengeluaranRequest.requester:id,name', 'pengeluaranRequest.approvalLogs.user:id,name'])
            ->orderBy('tanggal')
            ->get();

        foreach ($pengeluaran as $e) {
            $key = \Carbon\Carbon::parse($e->tanggal)->locale('id')->translatedFormat('F');
            $map[$key][] = sprintf(
                '%s · Pengeluaran — %s Rp %s',
                \Carbon\Carbon::parse($e->tanggal)->format('d/m'),
                $e->uraian ?? '-',
                number_format((int) $e->jumlah, 0, ',', '.'),
            );
        }

        return $map;
    }
}
