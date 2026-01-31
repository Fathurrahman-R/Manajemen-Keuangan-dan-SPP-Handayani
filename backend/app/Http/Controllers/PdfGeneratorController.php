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
        $viewData = [
            'rows'  => $kas,
            'bulan' => $request->bulan,
            'tahun' => $request->tahun,
        ];
        $pdf = Pdf::loadView('Laporan.kas-harian', $viewData)
            ->setPaper('A4', 'potrait');
        return $pdf->stream("Kas harian {$request->bulan}.pdf");
    }

    #[HeaderParameter('Authorization')]
    public function exportRekapBulanan(Request $request)
    {
        $rekap = app(LaporanService::class)->RekapBulanan($request)->toArray($request);
        $viewData = [
            'rows'  => $rekap,
            'tahun' => $request->tahun,
        ];
        $pdf = Pdf::loadView('Laporan.rekap-bulanan', $viewData)
            ->setPaper('A4', 'potrait');
        return $pdf->stream("Rekap Bulanan {$request->tahun}.pdf");
    }
}
