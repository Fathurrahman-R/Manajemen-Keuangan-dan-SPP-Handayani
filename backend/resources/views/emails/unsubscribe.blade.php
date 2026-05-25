<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berhenti Berlangganan</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; color: #333; }
        h1 { font-size: 1.5rem; margin-bottom: 1rem; }
        .form-group { margin: 1rem 0; }
        label { display: block; padding: 0.5rem 0; cursor: pointer; }
        button { background: #dc3545; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 1rem; }
        button:hover { background: #c82333; }
        .info { color: #666; font-size: 0.9rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <h1>Berhenti Berlangganan Notifikasi</h1>
    <p class="info">Email: <strong>{{ $email }}</strong></p>
    <p>Pilih jenis notifikasi yang ingin Anda hentikan:</p>

    <form method="POST" action="{{ url('/api/unsubscribe/' . $token) }}">
        @csrf
        <div class="form-group">
            <label>
                <input type="radio" name="type" value="tagihan_baru" {{ $currentType === 'tagihan_baru' ? 'checked' : '' }}>
                Tagihan Baru
            </label>
            <label>
                <input type="radio" name="type" value="reminder" {{ $currentType === 'reminder' ? 'checked' : '' }}>
                Pengingat Pembayaran
            </label>
            <label>
                <input type="radio" name="type" value="kwitansi" {{ $currentType === 'kwitansi' ? 'checked' : '' }}>
                Kwitansi Pembayaran
            </label>
            <label>
                <input type="radio" name="type" value="overdue" {{ $currentType === 'overdue' ? 'checked' : '' }}>
                Notifikasi Keterlambatan
            </label>
            <label>
                <input type="radio" name="type" value="all" {{ $currentType === 'all' ? 'checked' : '' }}>
                Semua Notifikasi
            </label>
        </div>

        <button type="submit">Berhenti Berlangganan</button>
    </form>
</body>
</html>
