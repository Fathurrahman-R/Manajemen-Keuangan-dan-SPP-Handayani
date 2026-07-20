<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    @php
        $headerColors = match($event) {
            'submitted' => 'background-color: #2c3e50;',
            'approved'  => 'background-color: #27ae60;',
            'rejected'  => 'background-color: #c0392b;',
            'disbursed' => 'background-color: #2980b9;',
            default     => 'background-color: #2c3e50;',
        };
    @endphp

    <div style="{{ $headerColors }} color: #ffffff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">Handayani</h1>
        <p style="margin: 5px 0 0;">{{ $title }}</p>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e0e0e0; border-top: none;">
        <p>Assalamu'alaikum Wr. Wb.</p>

        <p>{{ $notificationMessage }}</p>

        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr style="background-color: #f5f5f5;">
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold; width: 40%;">Uraian</td>
                <td style="padding: 10px; border: 1px solid #ddd;">{{ $pengeluaranRequest->uraian }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Jumlah</td>
                <td style="padding: 10px; border: 1px solid #ddd;">Rp {{ number_format($pengeluaranRequest->jumlah, 0, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #f5f5f5;">
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Tanggal Kebutuhan</td>
                <td style="padding: 10px; border: 1px solid #ddd;">{{ \Carbon\Carbon::parse($pengeluaranRequest->tanggal_kebutuhan)->format('d/m/Y') }}</td>
            </tr>
            @if($pengeluaranRequest->kategori_pengeluaran)
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Kategori</td>
                <td style="padding: 10px; border: 1px solid #ddd;">{{ $pengeluaranRequest->kategori_pengeluaran }}</td>
            </tr>
            @endif
            <tr style="background-color: #f5f5f5;">
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Status</td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    @php
                        $statusLabel = match($event) {
                            'submitted' => 'Menunggu Persetujuan',
                            'approved'  => 'Disetujui',
                            'rejected'  => 'Ditolak',
                            'disbursed' => 'Dicairkan',
                            default     => ucfirst($event),
                        };
                    @endphp
                    <strong>{{ $statusLabel }}</strong>
                </td>
            </tr>
            @if($reason && $event === 'rejected')
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold; color: #c0392b;">Alasan Penolakan</td>
                <td style="padding: 10px; border: 1px solid #ddd; color: #c0392b;">{{ $reason }}</td>
            </tr>
            @endif
        </table>

        <h3 style="margin-top: 25px; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Riwayat Proses</h3>
        <ul style="list-style-type: none; padding-left: 0; margin-bottom: 25px;">
            <li style="margin-bottom: 10px;">
                <strong>Diajukan oleh:</strong> {{ $requesterName }}<br>
                <span style="font-size: 12px; color: #7f8c8d;">Waktu: {{ $history['submitted'] ? $history['submitted']->created_at->format('d/m/Y H:i:s') : $pengeluaranRequest->created_at->format('d/m/Y H:i:s') }}</span>
            </li>
            
            {{-- Shown independently (not if/elseif) — a request rejected once and
                 then resubmitted + approved has BOTH a rejected and an approved
                 log; the rejection must stay visible, not get hidden by the
                 later approval. Rejected always precedes approved chronologically
                 (resubmission is required to move past `rejected`), so this
                 render order matches the actual timeline. --}}
            @if($history['rejected'])
            <li style="margin-bottom: 10px;">
                <strong>Ditolak oleh:</strong> {{ $history['rejected']->user->name ?? $history['rejected']->user->username ?? '-' }}<br>
                <span style="font-size: 12px; color: #7f8c8d;">Waktu: {{ $history['rejected']->created_at->format('d/m/Y H:i:s') }}</span>
            </li>
            @endif

            @if($history['approved'])
            <li style="margin-bottom: 10px;">
                <strong>Disetujui oleh:</strong> {{ str_starts_with($history['approved']->note ?? '', 'Auto-approved') ? 'Sistem (disetujui otomatis)' : ($history['approved']->user->name ?? $history['approved']->user->username ?? '-') }}<br>
                <span style="font-size: 12px; color: #7f8c8d;">Waktu: {{ $history['approved']->created_at->format('d/m/Y H:i:s') }}</span>
            </li>
            @endif

            @if($history['disbursed'])
            <li style="margin-bottom: 10px;">
                <strong>Dicairkan oleh:</strong> {{ $history['disbursed']->user->name ?? $history['disbursed']->user->username ?? '-' }}<br>
                <span style="font-size: 12px; color: #7f8c8d;">Waktu: {{ $history['disbursed']->created_at->format('d/m/Y H:i:s') }}</span>
            </li>
            @endif
        </ul>

        @if($event === 'submitted')
        <p>Silakan login ke dashboard untuk meninjau dan menyetujui/menolak request ini.</p>
        @elseif($event === 'approved')
        <p>Anda dapat melakukan pencairan melalui halaman Pengeluaran di dashboard.</p>
        @elseif($event === 'rejected')
        <p>Anda dapat mengubah dan mengajukan kembali request ini melalui dashboard.</p>
        @endif

        <p>Wassalamu'alaikum Wr. Wb.</p>

        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

        <p style="font-size: 12px; color: #888;">
            Email ini dikirim secara otomatis oleh sistem Handayani. Mohon tidak membalas email ini.
        </p>

        <p style="font-size: 12px; color: #888;">
            Untuk mengatur notifikasi approval pengeluaran yang ingin Anda terima, buka menu Profil di admin panel.
        </p>
    </div>
</body>
</html>
