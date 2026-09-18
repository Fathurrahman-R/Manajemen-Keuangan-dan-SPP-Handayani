<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Pembayaran (Gabungan)</title>

    <style>
        @include('partials.kwitansi-style')

        .kwitansi-page {
            page-break-after: always;
        }
    </style>
</head>
<body>
{{-- Watermark position:fixed sehingga DomPDF menggambarnya ulang di tiap halaman. --}}
<img src="{{ $logo }}" alt="Watermark" class="watermark">
@foreach($items as $item)
    <div @class(['kwitansi-page' => ! $loop->last])>
        @include('partials.kwitansi-content', $item)
    </div>
@endforeach
</body>
</html>
