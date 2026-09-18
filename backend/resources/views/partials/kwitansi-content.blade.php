<table  style="border-collapse: collapse;">
    <tr>
        <td style="width: 25%;height: 10px;" rowspan="2">
            <img src="{{ $logo }}" alt="Logo Sekolah" style=" margin-bottom:6px;height: 60px;padding-left: 10px">
        </td>
        <td rowspan="2">
            <h1 style="text-align: center;margin: 4px 0 0 0;padding: 0;font-size: 22px;color: green">
                {{ $setting['nama_sekolah'] }}
            </h1>
            <h3 style="text-align: center;margin: 0;color: green">(TK - KB - MIS Handayani)</h3>
            <p style="text-align: center;margin: 0;padding: 0;font-size: 10px;color: green">
                {{ $setting['alamat'] }}
            </p>
        </td>
        <td></td>
    </tr>
    <tr></tr>
    <tr>
        <td colspan="2">
            <p style="text-align: left;margin: 0;padding: 0;font-size: 9px;color: green">
                Email: {{ $setting['email'] }}
            </p>
        </td>
        <td style="width: 20%" >
            <p style="text-align: right;margin: 0;padding: 0;font-size: 9px;color: green">
                Telp: {{ $setting['telepon'] }}
            </p>

        </td>
    </tr>
    <tr>
        <td colspan="3">
            <hr style="border:0; border-bottom:1px dashed #000; margin:6px 0;">
            <div style="text-align: center;font-weight:bold; margin:5px 0;">
                <p style="padding: 0;margin: 0;color: green">
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
            <td class="label body-text" style="color: green"><strong>Sudah terima dari</strong></td>
            <td>:</td>
            <td><span class="value-line body-text" style="color: green">{{ $pembayar }}</span></td>
        </tr>

        <tr>
            <td class="label body-text" style="color: green"><strong>Untuk Pembayaran</strong></td>
            <td>:</td>
            <td><span class="value-line body-text" style="color: green">{{ $untuk }}</span></td>
        </tr>

        <tr>
            <td class="label body-text" style="color: green"><strong>Terbilang</strong></td>
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
                <div class="amount-box" style="">
                    <p class=" body-text " style="margin: 3px;color: green">
                        Rp{{ number_format($jumlah, 0, ',', '.') }}
                    </p>
                </div>
            </td>
            <td >
                <p class=" body-text" style="text-align: right;color: green">
                    {{ $setting['lokasi'] }}, {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
                </p>
            </td>
        </tr>
        <tr>
            <td>
                <br>
                <p class=" body-text" style="color: green;padding: 0;margin: 4px 0 0 0">
                    Bendahara,
                </p>
                <br><br><br>
                <u class=" body-text" style="padding: 0;margin: 0;color: green">{{ $setting['bendahara'] }}</u>
            </td>
            <td>
                <strong class=" body-text" style="padding: 0;margin: 4px 0 0 0;color: green">
                    Mengetahui,
                </strong>
                <p class=" body-text" style="padding: 0;margin: 0;color: green">
                    Kepala Sekolah
                </p>
                <br><br><br>
                <u class=" body-text" style="padding: 0;margin: 0;color: green">{{ $setting['kepala_sekolah'] }}</u>
            </td>
        </tr>
    </table>

</div>
