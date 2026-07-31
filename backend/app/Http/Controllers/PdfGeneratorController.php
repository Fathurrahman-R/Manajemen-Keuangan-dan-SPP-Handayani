<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Services\LaporanService;
use App\Services\Notifications\KwitansiPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PdfGeneratorController extends Controller
{
    #[HeaderParameter('Authorization')]
    public function get(string $kode_pembayaran)
    {
        $appSetting = AppSetting::query()->first();
        if (! $appSetting) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => [
                        'informasi sekolah tidak ditemukan! mohon untuk mengisi informasi sekolah terlebih dahulu.',
                    ],
                ],
            ], 404));
        }
        // Ambil resource kwitansi sebagai array data sederhana
        $resource = PembayaranController::kwitansi($kode_pembayaran); // KwitansiResource
        $data = $resource->toArray(request());

        $viewData = app(KwitansiPdfService::class)->viewDataFromArray($data);

        $pdf = Pdf::loadView('kwitansi', $viewData)
            ->setPaper('A6', 'landscape');

        return $pdf->stream("kwitansi-{$kode_pembayaran}.pdf");
    }

    /**
     * Gabungkan seluruh kwitansi yang cocok dengan filter aktif halaman
     * Pembayaran ke dalam SATU file PDF (satu kwitansi per halaman) supaya
     * admin bisa mencetak banyak sekaligus.
     *
     * Filter yang diterima sengaja identik dengan PembayaranController::grouped
     * supaya frontend tinggal meneruskan parameter yang sama persis — apa yang
     * terlihat di layar itu juga yang tercetak.
     */
    #[HeaderParameter('Authorization')]
    #[QueryParameter('search', description: 'Pencarian nama / nis siswa', required: false, example: 'Ahmad')]
    #[QueryParameter('jenjang', description: 'Filter jenjang (TK/MI/KB)', required: false, example: 'MI')]
    #[QueryParameter('kelas_id', description: 'Filter kelas (id)', required: false, example: 3)]
    #[QueryParameter('metode', description: 'Filter metode pembayaran (offline/online_midtrans)', required: false, example: 'offline')]
    #[QueryParameter('tahun_ajaran_id', description: 'Filter periode ajaran (0 atau all_periods=1 untuk semua periode)', required: false, example: 1)]
    public function bulkKwitansi(Request $request)
    {
        $appSetting = AppSetting::query()->first();
        if (! $appSetting) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => [
                        'informasi sekolah tidak ditemukan! mohon untuk mengisi informasi sekolah terlebih dahulu.',
                    ],
                ],
            ], 404));
        }

        $user = Auth::user();

        $tahunAjaranId = $request->input('tahun_ajaran_id');
        $allPeriods = $request->boolean('all_periods')
            || ($tahunAjaranId !== null && $tahunAjaranId !== '' && (int) $tahunAjaranId === 0);
        if ($allPeriods) {
            $tahunAjaranId = null;
        }

        $search = $request->input('search');
        $jenjang = $request->input('jenjang');
        $kelasId = $request->input('kelas_id');
        $metode = $request->input('metode');

        $query = Pembayaran::query()
            ->where('branch_id', $user->branch_id)
            ->whereHas('tagihan', function ($q) use ($tahunAjaranId, $search, $jenjang, $kelasId) {
                if ($tahunAjaranId) {
                    $q->where('tahun_ajaran_id', (int) $tahunAjaranId);
                }

                $q->whereHas('siswa', function ($qs) use ($search, $jenjang, $kelasId) {
                    if ($search) {
                        $qs->where(function ($w) use ($search) {
                            $w->where('nama', 'like', "%{$search}%")
                                ->orWhere('nis', 'like', "%{$search}%");
                        });
                    }
                    if ($jenjang) {
                        $qs->where('jenjang', $jenjang);
                    }
                    if (! is_null($kelasId) && $kelasId !== '') {
                        $qs->where('kelas_id', (int) $kelasId);
                    }
                });
            });

        if ($metode) {
            $query->where('metode', $metode);
        }

        // Siswa non-admin hanya boleh mencetak kwitansinya sendiri (mirror
        // pembatasan yang sama di PembayaranController::grouped).
        if ($user && ! $user->hasAnyRole(['superadmin', 'admin'])) {
            $nis = $user->siswa?->nis ?? $user->username;
            $query->whereHas('tagihan', function ($q) use ($nis) {
                $q->where('nis', $nis);
            });
        }

        $limit = (int) config('pdf.kwitansi_bulk_limit', 200);
        $total = (clone $query)->count();

        if ($total === 0) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['Tidak ada pembayaran yang cocok dengan filter saat ini.'],
                ],
            ], 422));
        }

        if ($total > $limit) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => [
                        "Filter saat ini mencakup {$total} kwitansi, melebihi batas {$limit} per unduhan. Persempit filter (mis. pilih kelas atau periode tertentu) lalu coba lagi.",
                    ],
                ],
            ], 422));
        }

        $rows = $query->with(['tagihan.siswa:id,nis,nama'])->get()
            ->sortBy([
                fn ($a, $b) => strcasecmp($a->tagihan?->siswa?->nama ?? '', $b->tagihan?->siswa?->nama ?? ''),
                fn ($a, $b) => strcmp((string) $a->tanggal, (string) $b->tanggal),
            ])
            ->values();

        $service = app(KwitansiPdfService::class);
        $items = $rows->map(fn (Pembayaran $pembayaran) => $service->viewDataFor($pembayaran))->all();

        $pdf = Pdf::loadView('kwitansi-bulk', [
            'items' => $items,
            'logo' => $items[0]['logo'],
        ])->setPaper('A6', 'landscape');

        return $pdf->download('kwitansi-gabungan-'.now()->format('Ymd-His').'.pdf');
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
            'rows' => $kas,
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
            'rows' => $rekap,
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
