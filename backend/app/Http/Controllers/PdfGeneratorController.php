<?php

namespace App\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfGeneratorController extends Controller
{
    public function index(string $kode_pembayaran)
    {
        // Ambil resource kwitansi sebagai array data sederhana
        $resource = PembayaranController::kwitansi($kode_pembayaran); // KwitansiResource
        $data = $resource->toArray(request());

        // Fallback logo jika null / tidak ada
        $logo = $data['setting']['logo'] ?? public_path('favicon.ico');

        // Gabungkan ke payload view (blade mengharapkan variabel terpisah)
        $viewData = [
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
