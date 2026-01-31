<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kas Harian</title>
    <style>
        @page { size: A4; margin: 20px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h4 { text-align: center; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
<h4>BUKU / DOKUMEN</h4>
<h4>REKAPTULASI TRANSAKSI DATA BULANAN</h4>
<p style="text-align:center; margin-top:4px;">Periode: {{ $tahun }}</p>
<table>
    <thead>
    <tr>
        <th style="width:20px;">No</th>
        <th style="width:110px;">Bulan</th>
        <th style="width:110px;">Total Pemasukan (Rp)</th>
        <th style="width:110px;">Total Pengeluaran (Rp)</th>
        <th style="width:110px;">Saldo Akhir(Rp)</th>
        <th style="width:110px;">Catatan</th>
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $index => $row)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $row['tanggal'] ?? '-' }}</td>
            <td class="text-right">{{ number_format($row['total_masuk'] ?? 0, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($row['total_keluar'] ?? 0, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($row['saldo'] ?? 0, 0, ',', '.') }}</td>
            <td>-</td>
        </tr>
    @empty
        <tr>
            <td colspan="7" style="text-align:center;">Tidak ada data rekap bulanan pada periode ini.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
