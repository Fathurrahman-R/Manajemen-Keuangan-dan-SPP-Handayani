<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kredensial Akun Siswa</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 20px;
        }
        html, body {
            font-family: "Arial", sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 10px;
        }

        h2 {
            text-align: center;
            margin-bottom: 15px;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th, table td {
            border: 1px solid #333;
            padding: 8px 10px;
            text-align: left;
        }

        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        table td {
            vertical-align: middle;
        }

        .footer-note {
            margin-top: 15px;
            font-size: 10px;
            color: #555;
        }
    </style>
</head>
<body>
    <h2>Kredensial Akun Siswa</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 30%;">Nama</th>
                <th style="width: 25%;">Username (NIS)</th>
                <th style="width: 40%;">Password</th>
            </tr>
        </thead>
        <tbody>
            @foreach($credentials as $index => $credential)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $credential['nama'] }}</td>
                    <td>{{ $credential['username'] }}</td>
                    <td>{{ $credential['password_pattern'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer-note">
        * Password default adalah tanggal lahir siswa dengan format DDMMYYYY (contoh: 25032015 untuk tanggal lahir 25 Maret 2015).
    </p>
</body>
</html>
