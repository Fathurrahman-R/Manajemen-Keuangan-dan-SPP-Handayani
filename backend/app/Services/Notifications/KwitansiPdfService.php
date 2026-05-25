<?php

namespace App\Services\Notifications;

use App\Helpers\NotificationHelper;
use App\Models\Pembayaran;
use Barryvdh\DomPDF\Facade\Pdf;

class KwitansiPdfService
{
    /**
     * Generate a kwitansi PDF for a pembayaran and return the raw PDF content.
     */
    public function generate(Pembayaran $pembayaran): string
    {
        $pembayaran->loadMissing(['tagihan.jenisTagihan', 'tagihan.siswa']);

        $data = [
            'kode_pembayaran' => $pembayaran->kode_pembayaran,
            'tanggal' => $pembayaran->created_at->format('d/m/Y'),
            'siswa_nama' => $pembayaran->tagihan->siswa->nama ?? '-',
            'siswa_nis' => $pembayaran->tagihan->siswa->nis ?? '-',
            'jenis_tagihan' => $pembayaran->tagihan->jenisTagihan->nama ?? '-',
            'jumlah' => NotificationHelper::formatRupiah($pembayaran->jumlah),
            'metode' => $pembayaran->metode,
            'pembayar' => $pembayaran->pembayar,
        ];

        $pdf = Pdf::loadView('emails.notifications.kwitansi-pdf', $data)
            ->setPaper('A5', 'portrait');

        return $pdf->output();
    }
}
