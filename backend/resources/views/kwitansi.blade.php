<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Pembayaran</title>

    <style>
        @page {
            size: A6 landscape;
            margin: 10px;
        }
        html, body {
            font-family: "Arial", sans-serif;
            font-size: 11px;
            margin: 2px 0;
            padding: 8px 15px;
        }

        /* Kontainer landscape */
        .kwitansi {
            width: 100%;
            display: flex;
            flex-direction: row;
            gap: 20px;
        }

        /* Bagian kiri: logo + identitas sekolah */
        .left-section {
            width: 35%;
            text-align: center;
            border-right: 1px dashed #444;
            padding-right: 12px;
        }

        .left-section img {
            width: 70px;
            margin-right: 0;
            height: auto;
            margin-bottom: 5px;
        }

        .title {
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .line {
            border-bottom: 1px dashed #000;
            margin: 5px 0;
        }

        /* Bagian kanan: isi kwitansi */
        .right-section {
            width: 100%;
        }

        table {
            width: 100%;
            font-size: 11px;
        }

        .label {
            width: 120px;
            vertical-align: top;
        }

        .value-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            width: 100%;
        }

        .amount-box {
            border: 1px solid #000;
            padding: 5px 0;
            margin: 8px 0 0 0;
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }

        .ttd {
            margin-top: 0px;
            width: 100%;
        }

        .ttd td {
            text-align: center;
            font-size: 11px;
        }
    </style>
</head>
<body>

<table  style="border-collapse: collapse;">
    <tr>
        <td style="width: 25%;height: 10px;" rowspan="2">
            <img src="{{ $setting['logo'] }}" style=" margin-bottom:4px;">
        </td>
        <td rowspan="2">
            <h1 style="text-align: center;margin: 4px 0;font-size: 22px">
                {{ $setting['nama_sekolah'] }}
            </h1>
            <p style="text-align: center;margin: 0;padding: 0">
                {{ $setting['alamat'] }}
            </p>
        </td>
        <td></td>
    </tr>
    <tr></tr>
    <tr>
        <td colspan="2">
            <p style="text-align: left;margin: 0;padding: 0">
                Email: {{ $setting['email'] }}
            </p>
        </td>
        <td style="width: 20%" >
            <p style="text-align: right;margin: 0;padding: 0">
                Telp: {{ $setting['telepon'] }}
            </p>

        </td>
    </tr>
    <tr>
        <td colspan="3">
            <hr style="border:0; border-bottom:1px dashed #000; margin:6px 0;">
            <div style="text-align: center;font-weight:bold; margin:5px 0;">K W I T A N S I</div>
            <hr style="border:0; border-bottom:1px dashed #000; margin:6px 0;">
        </td>
    </tr>
</table>
<div class="right-section">
    <table>
        <tr>
            <td class="label">Sudah terima dari</td>
            <td>:</td>
            <td><span class="value-line">{{ $pembayar }}</span></td>
        </tr>

        <tr>
            <td class="label">Untuk Pembayaran</td>
            <td>:</td>
            <td><span class="value-line">{{ $untuk }}</span></td>
        </tr>

        <tr>
            <td class="label">Tanggal</td>
            <td>:</td>
            <td>
                            <span class="value-line">
                                {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
                            </span>
            </td>
        </tr>
    </table>

    <div class="amount-box">
        Rp {{ number_format($jumlah, 0, ',', '.') }}
    </div>

    <table class="ttd">
        <tr>
            <td>
                <p style="text-align: left">
                    <strong>Terbilang: </strong>{{ ucfirst($sejumlah) }}
                </p>
            </td>
            <td>
                <p style="text-align: right">
                    {{ $setting['lokasi'] }}, {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
                </p>
            </td>
        </tr>
        <tr>
            <td>
                <br>
                Bendahara,<br><br><br>
                <u>{{ $setting['bendahara'] }}</u>
            </td>

            <td>
                Mengetahui,<br>
                Kepala Sekolah<br><br><br>
                <u>{{ $setting['kepala_sekolah'] }}</u>
            </td>
        </tr>
    </table>

</div>

</body>
</html>
