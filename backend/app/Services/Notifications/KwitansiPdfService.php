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
        $pdf = Pdf::loadView('kwitansi', $this->viewDataFor($pembayaran))
            ->setPaper('A6', 'landscape');

        return $pdf->output();
    }

    /**
     * Payload untuk blade kwitansi (variabel flat, bukan nested resource).
     *
     * Dipakai bersama oleh kwitansi tunggal (PdfGeneratorController::get),
     * kwitansi gabungan (PdfGeneratorController::bulkKwitansi), dan attachment
     * email. Satu sumber supaya ketiganya tidak pernah divergen.
     *
     * @return array<string, mixed>
     */
    public function viewDataFor(Pembayaran $pembayaran): array
    {
        return $this->viewDataFromArray((new KwitansiResource($pembayaran))->toArray(request()));
    }

    /**
     * Varian viewDataFor untuk pemanggil yang sudah memegang array hasil
     * KwitansiResource (mis. controller yang me-resolve pembayaran lewat jalur
     * lain dengan scoping tersendiri).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function viewDataFromArray(array $data): array
    {
        return [
            'kode_pembayaran' => $data['kode_pembayaran'],
            'setting' => $data['setting'] ?? [],
            'tanggal' => $data['tanggal'] ?? null,
            'pembayar' => $data['pembayar'] ?? null,
            'jumlah' => $data['jumlah'] ?? 0,
            'untuk' => $data['untuk'] ?? '-',
            'sejumlah' => $data['sejumlah'] ?? '-',
            'logo' => $this->resolveLogo($data['setting']['logo'] ?? null),
        ];
    }

    /**
     * Absolute path logo yang bisa dibaca DomPDF; fallback ke favicon.
     */
    public function resolveLogo(?string $logoRelative): string
    {
        if ($logoRelative && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoRelative)) {
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($logoRelative);

            if ($this->isRenderableImage($path)) {
                return $path;
            }

            \Illuminate\Support\Facades\Log::warning('Logo dilewati di kwitansi: format tidak didukung GD terpasang', [
                'path' => $logoRelative,
            ]);
        }

        return public_path('favicon.ico');
    }

    /**
     * Apakah GD yang terpasang benar-benar bisa membaca format gambar ini.
     *
     * DomPDF melempar exception (bukan sekadar melewati gambar) kalau formatnya
     * tidak didukung, dan itu menggagalkan seluruh kwitansi — bukan cuma logonya.
     */
    private function isRenderableImage(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'webp' => function_exists('imagecreatefromwebp'),
            'jpg', 'jpeg' => function_exists('imagecreatefromjpeg'),
            'png' => function_exists('imagecreatefrompng'),
            'gif' => function_exists('imagecreatefromgif'),
            default => true,
        };
    }

    /**
     * Filename yang dipakai untuk attachment.
     */
    public function filenameFor(Pembayaran $pembayaran): string
    {
        return 'kwitansi-'.$pembayaran->kode_pembayaran.'.pdf';
    }
}
