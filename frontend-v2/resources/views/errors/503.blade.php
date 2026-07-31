<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Layanan Tidak Tersedia — Handayani</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            color: #0f172a;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .kotak {
            max-width: 32rem;
            padding: 2.5rem;
            text-align: center;
        }

        .kode {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin: 0 0 0.5rem;
        }

        .judul {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.75rem;
        }

        .pesan {
            font-size: 0.95rem;
            line-height: 1.6;
            color: #475569;
            margin: 0 0 1.75rem;
        }

        .tombol {
            display: inline-block;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            background: #0f172a;
            color: #f8fafc;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
        }

        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #f8fafc; }
            .pesan { color: #94a3b8; }
            .tombol { background: #f8fafc; color: #0f172a; }
        }
    </style>
</head>
<body>
    <div class="kotak">
        <p class="kode">503</p>
        <h1 class="judul">Layanan Sedang Tidak Tersedia</h1>
        <p class="pesan">
            {{ $exception?->getMessage() ?: 'Server aplikasi sedang tidak dapat dihubungi. Coba beberapa saat lagi atau hubungi administrator.' }}
        </p>
        <a class="tombol" href="{{ url()->current() }}">Coba Lagi</a>
    </div>
</body>
</html>
