<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Pembayaran {{ $kode_pembayaran }}</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 20px;
        }

        html, body {
            font-family: "Arial", sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .header p {
            margin: 4px 0 0 0;
            font-size: 11px;
            color: #666;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .details-table tr td {
            padding: 6px 4px;
            vertical-align: top;
        }

        .details-table .label {
            width: 140px;
            font-weight: bold;
            color: #333;
        }

        .details-table .separator {
            width: 10px;
            text-align: center;
        }

        .details-table .value {
            color: #444;
        }

        .amount-box {
            margin-top: 20px;
            border: 2px solid #2e7d32;
            padding: 10px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #2e7d32;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>KWITANSI PEMBAYARAN</h1>
        <p>No: {{ $kode_pembayaran }}</p>
    </div>

    <table class="details-table">
        <tr>
            <td class="label">Tanggal</td>
            <td class="separator">:</td>
            <td class="value">{{ $tanggal }}</td>
        </tr>
        <tr>
            <td class="label">Nama Siswa</td>
            <td class="separator">:</td>
            <td class="value">{{ $siswa_nama }}</td>
        </tr>
        <tr>
            <td class="label">NIS</td>
            <td class="separator">:</td>
            <td class="value">{{ $siswa_nis }}</td>
        </tr>
        <tr>
            <td class="label">Jenis Tagihan</td>
            <td class="separator">:</td>
            <td class="value">{{ $jenis_tagihan }}</td>
        </tr>
        <tr>
            <td class="label">Metode Pembayaran</td>
            <td class="separator">:</td>
            <td class="value">{{ $metode }}</td>
        </tr>
        <tr>
            <td class="label">Pembayar</td>
            <td class="separator">:</td>
            <td class="value">{{ $pembayar }}</td>
        </tr>
    </table>

    <div class="amount-box">
        {{ $jumlah }}
    </div>

    <div class="footer">
        <p>Dokumen ini digenerate secara otomatis oleh sistem.</p>
    </div>
</body>
</html>
