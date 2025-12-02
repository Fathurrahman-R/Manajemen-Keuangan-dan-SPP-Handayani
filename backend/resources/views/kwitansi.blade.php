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
            margin: 6px 6px;
            padding: 8px 15px;
        }

        .left-section img {
            width: 70px;
            margin-right: 0;
            height: auto;
            margin-bottom: 5px;
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
            padding: 3px 0;
            margin: 8px 0 0 0;
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
        .body-text {
            font-size: 10px;
        }
        .watermark {
            position: fixed;            /* stay in place for all pages */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60%;                 /* adjust size as needed */
            opacity: 0.06;              /* watermark transparency */
            /* z-index is not required in DomPDF; draw order handles stacking */
        }
    </style>
</head>
<body>
<img src="{{ $logo }}" alt="Watermark" class="watermark">
<table  style="border-collapse: collapse;">
    <tr>
        <td style="width: 25%;height: 10px;" rowspan="2">
            <img src="{{ $logo }}" alt="Logo Sekolah" style=" margin-bottom:6px;height: 60px;padding-left: 10px">
        </td>
        <td rowspan="2">
            <h1 style="text-align: center;margin: 4px 0 0 0;padding: 0;font-size: 22px">
                {{ $setting['nama_sekolah'] }}
            </h1>
            <h3 style="text-align: center;margin: 0;">(TK - KB - MIS Handayani)</h3>
            <p style="text-align: center;margin: 0;padding: 0;font-size: 10px">
                {{ $setting['alamat'] }}
            </p>
        </td>
        <td></td>
    </tr>
    <tr></tr>
    <tr>
        <td colspan="2">
            <p style="text-align: left;margin: 0;padding: 0;font-size: 9px">
                Email: {{ $setting['email'] }}
            </p>
        </td>
        <td style="width: 20%" >
            <p style="text-align: right;margin: 0;padding: 0;font-size: 9px">
                Telp: {{ $setting['telepon'] }}
            </p>

        </td>
    </tr>
    <tr>
        <td colspan="3">
            <hr style="border:0; border-bottom:1px dashed #000; margin:6px 0;">
            <div style="text-align: center;font-weight:bold; margin:5px 0;">
                <p style="padding: 0;margin: 0">
                    KWITANSI {{ $kode_pembayaran  }}
                </p>
            </div>
            <hr style="border:0; border-bottom:1px dashed #000; margin:6px 0;">
        </td>
    </tr>
</table>
<div class="right-section" style="margin-top: 6px">
    <table>
        <tr>
            <td class="label body-text" ><strong>Sudah terima dari</strong></td>
            <td>:</td>
            <td><span class="value-line body-text" >{{ $pembayar }}</span></td>
        </tr>

        <tr>
            <td class="label body-text" ><strong>Untuk Pembayaran</strong></td>
            <td>:</td>
            <td><span class="value-line body-text" >{{ $untuk }}</span></td>
        </tr>

        <tr>
            <td class="label body-text" ><strong>Terbilang</strong></td>
            <td>:</td>
            <td>
                            <strong class="value-line body-text" style="color: green">
                                {{ $sejumlah }}
                            </strong>
            </td>
        </tr>
    </table>



    <table class="ttd">
        <tr>
            <td style="width: 50%">
                <div class="amount-box" >
                    <p class=" body-text " style="margin: 3px;color: green">
                        Rp{{ number_format($jumlah, 0, ',', '.') }}
                    </p>
                </div>
            </td>
            <td >
                <p class=" body-text" style="text-align: right">
                    {{ $setting['lokasi'] }}, {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
                </p>
            </td>
        </tr>
        <tr>
            <td>
                <br>
                <p class=" body-text" style="padding: 0;margin: 4px 0 0 0">
                    Bendahara,
                </p>
                <br><br><br>
                <u class=" body-text" style="padding: 0;margin: 0">{{ $setting['bendahara'] }}</u>
            </td>
            <td>
                <strong class=" body-text" style="padding: 0;margin: 4px 0 0 0">
                    Mengetahui,
                </strong>
                <p class=" body-text" style="padding: 0;margin: 0">
                    Kepala Sekolah
                </p>
                <br><br><br>
                <u class=" body-text" style="padding: 0;margin: 0">{{ $setting['kepala_sekolah'] }}</u>
            </td>
        </tr>
    </table>

</div>

</body>
</html>
