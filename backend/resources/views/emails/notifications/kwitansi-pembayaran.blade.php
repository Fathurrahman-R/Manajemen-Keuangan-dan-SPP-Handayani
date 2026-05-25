<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi Pembayaran</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #2c3e50; color: #ffffff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">Handayani</h1>
        <p style="margin: 5px 0 0;">Kwitansi Pembayaran</p>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e0e0e0; border-top: none;">
        <p>Assalamu'alaikum Wr. Wb.</p>

        <p>Yth. Orang Tua/Wali dari <strong>{{ $siswa->nama }}</strong>,</p>

        <p>Berikut adalah konfirmasi pembayaran yang telah diterima:</p>

        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5; font-weight: bold;">Kode Pembayaran</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $pembayaran->kode_pembayaran }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5; font-weight: bold;">Tanggal</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $pembayaran->tanggal }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5; font-weight: bold;">Metode Pembayaran</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $pembayaran->metode }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5; font-weight: bold;">Jumlah</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ \App\Helpers\NotificationHelper::formatRupiah($pembayaran->jumlah) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5; font-weight: bold;">Jenis Tagihan</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $pembayaran->tagihan->jenis_tagihan->nama ?? '-' }}</td>
            </tr>
        </table>

        <p>Terima kasih atas pembayarannya. Simpan email ini sebagai bukti pembayaran.</p>

        <p>Wassalamu'alaikum Wr. Wb.</p>

        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

        <p style="font-size: 12px; color: #888;">
            Jika Anda tidak ingin menerima email ini, silakan
            <a href="{{ $unsubscribeUrl }}" style="color: #888;">klik di sini untuk berhenti berlangganan</a>.
        </p>
    </div>
</body>
</html>
