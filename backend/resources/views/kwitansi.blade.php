<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Pembayaran</title>

    <style>
        @include('partials.kwitansi-style')
    </style>
</head>
<body>
<img src="{{ $logo }}" alt="Watermark" class="watermark">
@include('partials.kwitansi-content')
</body>
</html>
