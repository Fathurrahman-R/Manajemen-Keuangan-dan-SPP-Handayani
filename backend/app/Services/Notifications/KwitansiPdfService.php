<?php

namespace App\Services\Notifications;

use App\Http\Controllers\PdfGeneratorController;
use App\Http\Resources\KwitansiResource;
use App\Models\Pembayaran;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Generate kwitansi PDF yang diattach ke email notifikasi.
 *
 * Penting: PDF yang dilampirkan ke email HARUS sama dengan PDF yang
 * dihasilkan oleh tombol "Cetak Kwitansi" di admin panel (lihat
 * PdfGeneratorController::get). Service ini mereuse KwitansiResource yang
 * sama supaya tidak ada divergensi antara PDF email dan PDF admin/portal.
 *
 * Dibuat langsung dari KwitansiResource ($pembayaran), BUKAN lewat
 * PembayaranController::kwitansi() -- controller itu me-refetch pembayaran
 * dengan scope Auth::user()->branch_id, yang selalu null di sini karena
 * KwitansiPembayaranNotification adalah queued job (dieksekusi queue
 * worker tanpa user yang login), menyebabkan attachment PDF gagal senyap.
 */
class KwitansiPdfService
{
    /**
     * Generate kwitansi PDF dan return raw PDF content (bytes).
     */
    public function generate(Pembayaran $pembayaran): string
    {
        $resource = new KwitansiResource($pembayaran);
        $data = $resource->toArray(request());

        // Resolve logo absolute path from public disk; fallback to public favicon
        $logoRelative = $data['setting']['logo'] ?? null;
        $logo = null;
        if ($logoRelative && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoRelative)) {
            // Absolute filesystem path DomPDF can read
            $logo = \Illuminate\Support\Facades\Storage::disk('public')->path($logoRelative);
        }
        if (! $logo) {
            $logo = public_path('favicon.ico');
        }

        $viewData = [
            'kode_pembayaran' => $data['kode_pembayaran'],
            'setting' => $data['setting'] ?? [],
            'tanggal' => $data['tanggal'] ?? null,
            'pembayar' => $data['pembayar'] ?? null,
            'jumlah' => $data['jumlah'] ?? 0,
            'untuk' => $data['untuk'] ?? '-',
            'sejumlah' => $data['sejumlah'] ?? '-',
            'logo' => $logo,
        ];

        $pdf = Pdf::loadView('kwitansi', $viewData)
            ->setPaper('A6', 'landscape');

        return $pdf->output();
    }

    /**
     * Filename yang dipakai untuk attachment.
     */
    public function filenameFor(Pembayaran $pembayaran): string
    {
        return 'kwitansi-'.$pembayaran->kode_pembayaran.'.pdf';
    }
}
