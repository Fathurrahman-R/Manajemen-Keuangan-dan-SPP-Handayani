<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #2c3e50; color: #ffffff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">Handayani</h1>
        <p style="margin: 5px 0 0;">Verifikasi Email</p>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e0e0e0; border-top: none;">
        <p>Assalamu'alaikum Wr. Wb.</p>

        <p>Gunakan kode verifikasi berikut untuk mengonfirmasi alamat email Anda:</p>

        <div style="text-align: center; margin: 30px 0;">
            <div style="background-color: #f5f5f5; border: 2px dashed #2c3e50; border-radius: 8px; padding: 20px; display: inline-block;">
                <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #2c3e50;">{{ $otp }}</span>
            </div>
        </div>

        <p>Kode ini berlaku selama <strong>10 menit</strong>. Jangan berikan kode ini kepada siapapun.</p>

        <p>Jika Anda tidak meminta verifikasi email, abaikan email ini.</p>

        <p>Wassalamu'alaikum Wr. Wb.</p>

        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

        <p style="font-size: 12px; color: #888;">
            Email ini dikirim secara otomatis. Mohon tidak membalas email ini.
        </p>
    </div>
</body>
</html>
