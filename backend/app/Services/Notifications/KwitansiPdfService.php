<?php

namespace App\Services\Notifications;

use App\Http\Controllers\PdfGeneratorController;
use App\Http\Controllers\PembayaranController;
use App\Models\AppSetting;
use App\Models\Pembayaran;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Generate kwitansi PDF yang diattach ke email notifikasi.
 *
 * Penting: PDF yang dilampirkan ke email HARUS sama dengan PDF yang
 * dihasilkan oleh tombol "Cetak Kwitansi" di admin panel (lihat
 * PdfGeneratorController::get). Service ini mereuse logika yang sama
 * supaya tidak ada divergensi antara PDF email dan PDF admin/portal.
 */
class KwitansiPdfService
{
    /**
     * Generate kwitansi PDF dan return raw PDF content (bytes).
     */
    public function generate(Pembayaran $pembayaran): string
    {
        // Reuse KwitansiResource lewat PembayaranController::kwitansi()
        // sehingga payload identik dengan endpoint admin.
        $resource = PembayaranController::kwitansi($pembayaran->kode_pembayaran);
        $data = $resource->toArray(request());

        $appSetting = AppSetting::query()->first();
        if ($appSetting) {
            $data['setting'] = array_merge($data['setting'] ?? [], [
                'nama_sekolah' => $appSetting->nama_sekolah ?? ($data['setting']['nama_sekolah'] ?? null),
                'lokasi' => $appSetting->lokasi ?? ($data['setting']['lokasi'] ?? null),
                'logo' => $appSetting->logo ?? ($data['setting']['logo'] ?? null),
            ]);
        }

        $viewData = [
            'kode_pembayaran' => $data['kode_pembayaran'],
            'kode_tagihan'    => $data['kode_tagihan'] ?? null,
            'setting'         => $data['setting'] ?? [],
            'tanggal'         => $data['tanggal'] ?? null,
            'pembayar'        => $data['pembayar'] ?? null,
            'siswa'           => $data['siswa'] ?? null,
            'jenis_tagihan'   => $data['jenis_tagihan'] ?? null,
            'jumlah'          => $data['jumlah'] ?? 0,
            'metode'          => $data['metode'] ?? '-',
            'sisa'            => $data['sisa'] ?? 0,
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
        return 'kwitansi-' . $pembayaran->kode_pembayaran . '.pdf';
    }
}
