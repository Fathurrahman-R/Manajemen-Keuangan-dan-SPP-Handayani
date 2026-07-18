<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berhasil Berhenti Berlangganan</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; color: #333; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h1>Berhasil Berhenti Berlangganan</h1>
    <div class="success">
        <p>Email <strong>{{ $email }}</strong> telah berhasil berhenti berlangganan dari notifikasi
            @if($type === 'all')
                <strong>semua jenis</strong>.
            @elseif($type === 'tagihan_baru')
                <strong>Tagihan Baru</strong>.
            @elseif($type === 'reminder')
                <strong>Pengingat Pembayaran</strong>.
            @elseif($type === 'kwitansi')
                <strong>Kwitansi Pembayaran</strong>.
            @elseif($type === 'overdue')
                <strong>Notifikasi Keterlambatan</strong>.
            @endif
        </p>
    </div>
    <p>Anda tidak akan menerima email notifikasi tersebut lagi.</p>
</body>
</html>
