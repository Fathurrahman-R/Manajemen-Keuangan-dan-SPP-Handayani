<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Tagihan</title>
    <style>
        @page { size: A4 landscape; margin: 18px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h4 { text-align: center; margin: 0; }
        h5 { text-align: center; margin: 0; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #000; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .meta { margin-top: 6px; font-size: 10px; }
        .meta span { margin-right: 12px; }
        tfoot td { font-weight: bold; background: #fafafa; }
    </style>
</head>
<body>
    <h4>{{ $branchName ?? 'Cabang' }}</h4>
    <h4>LAPORAN TAGIHAN</h4>
    @if(!empty($periode))
        <h5>Periode: {{ $periode }}</h5>
    @endif

    <div class="meta text-center">
        @if(!empty($jenjang))
            <span><strong>Jenjang:</strong> {{ $jenjang }}</span>
        @endif
        @if(!empty($statusFilter))
            <span><strong>Status:</strong> {{ implode(', ', (array) $statusFilter) }}</span>
        @endif
        <span><strong>Dicetak:</strong> {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</span>
    </div>

    @php
        $totalTagihan = 0;
        $totalTerbayar = 0;
        $totalSisa = 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width:24px;">No</th>
                <th style="width:80px;">Kode Tagihan</th>
                <th>Nama Siswa</th>
                <th style="width:60px;">NIS</th>
                <th style="width:60px;">Jenjang</th>
                <th style="width:60px;">Kelas</th>
                <th>Jenis Tagihan</th>
                <th style="width:80px;">Jatuh Tempo</th>
                <th style="width:80px;">Status</th>
                <th style="width:90px;">Total (Rp)</th>
                <th style="width:90px;">Terbayar (Rp)</th>
                <th style="width:90px;">Sisa (Rp)</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $i => $row)
            @php
                $jumlah = (int) ($row['jumlah'] ?? 0);
                $tmp = (int) ($row['tmp'] ?? 0);
                $sisa = max($jumlah - $tmp, 0);
                $totalTagihan += $jumlah;
                $totalTerbayar += $tmp;
                $totalSisa += $sisa;
            @endphp
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $row['kode_tagihan'] ?? '-' }}</td>
                <td>{{ $row['nama'] ?? '-' }}</td>
                <td>{{ $row['nis'] ?? '-' }}</td>
                <td class="text-center">{{ $row['jenjang'] ?? '-' }}</td>
                <td>{{ $row['kelas'] ?? '-' }}</td>
                <td>{{ $row['jenis_tagihan'] ?? '-' }}</td>
                <td>{{ $row['jatuh_tempo'] ?? '-' }}</td>
                <td>{{ $row['status'] ?? '-' }}</td>
                <td class="text-right">{{ number_format($jumlah, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($tmp, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($sisa, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="12" class="text-center">Tidak ada data tagihan untuk filter yang dipilih.</td>
            </tr>
        @endforelse
        </tbody>
        @if(count($rows ?? []) > 0)
            <tfoot>
                <tr>
                    <td colspan="9" class="text-right">TOTAL</td>
                    <td class="text-right">{{ number_format($totalTagihan, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalTerbayar, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalSisa, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
