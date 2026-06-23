<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Bulanan</title>
    <style>
        @page { size: A4 landscape; margin: 18px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h4 { text-align: center; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #000; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .catatan-list { margin: 0; padding-left: 14px; }
        .catatan-list li { margin-bottom: 2px; line-height: 1.35; }
        tfoot td { font-weight: bold; background: #fafafa; }
    </style>
</head>
<body>
    <h4>BUKU / DOKUMEN</h4>
    <h4>REKAPITULASI TRANSAKSI BULANAN</h4>
    <p class="text-center" style="margin-top:4px;">Periode: {{ $tahun }}</p>
    @php
        $totalMasuk = 0;
        $totalKeluar = 0;
    @endphp
    <table>
        <thead>
            <tr>
                <th style="width:24px;">No</th>
                <th style="width:90px;">Bulan</th>
                <th style="width:100px;">Total Pemasukan (Rp)</th>
                <th style="width:100px;">Total Pengeluaran (Rp)</th>
                <th style="width:100px;">Saldo Akhir (Rp)</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                @php
                    $bulanLabel = $row['tanggal'] ?? '-';
                    $masuk = (int) ($row['total_masuk'] ?? 0);
                    $keluar = (int) ($row['total_keluar'] ?? 0);
                    $saldo = (int) ($row['saldo'] ?? 0);
                    $totalMasuk += $masuk;
                    $totalKeluar += $keluar;
                    $lines = $catatan[$bulanLabel] ?? [];
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $bulanLabel }}</td>
                    <td class="text-right">{{ number_format($masuk, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($keluar, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($saldo, 0, ',', '.') }}</td>
                    <td>
                        @if (count($lines) > 0)
                            <ul class="catatan-list">
                                @foreach ($lines as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data rekap bulanan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($rows ?? []) > 0)
            <tfoot>
                <tr>
                    <td colspan="2" class="text-right">TOTAL</td>
                    <td class="text-right">{{ number_format($totalMasuk, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalKeluar, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalMasuk - $totalKeluar, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
