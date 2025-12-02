<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
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
}
