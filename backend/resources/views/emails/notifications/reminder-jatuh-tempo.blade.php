<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Jatuh Tempo</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #2c3e50; color: #ffffff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">Handayani</h1>
        <p style="margin: 5px 0 0;">Pengingat Jatuh Tempo Tagihan</p>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e0e0e0; border-top: none;">
        <p>Assalamu'alaikum Wr. Wb.</p>

        <p>Yth. Orang Tua/Wali dari <strong>{{ $siswa->nama }}</strong>,</p>

        <p>Ini adalah pengingat bahwa tagihan berikut akan jatuh tempo dalam <strong>{{ $daysBefore }} hari</strong>:</p>

        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5; font-weight: bold;">Jenis Tagihan</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $tagihan->jenis_tagihan->nama ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5; font-weight: bold;">Jumlah</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ \App\Helpers\NotificationHelper::formatRupiah($tagihan->jenis_tagihan->jumlah ?? 0) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5; font-weight: bold;">Jatuh Tempo</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $tagihan->jenis_tagihan->jatuh_tempo ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5; font-weight: bold;">Sisa Hari</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $daysBefore }} hari lagi</td>
            </tr>
        </table>

        <p>Mohon segera lakukan pembayaran sebelum tanggal jatuh tempo untuk menghindari keterlambatan. Terima kasih.</p>

        <p>Wassalamu'alaikum Wr. Wb.</p>

        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

        <p style="font-size: 12px; color: #888;">
            Jika Anda tidak ingin menerima email ini, silakan
            <a href="{{ $unsubscribeUrl }}" style="color: #888;">klik di sini untuk berhenti berlangganan</a>.
        </p>
    </div>
</body>
</html>
