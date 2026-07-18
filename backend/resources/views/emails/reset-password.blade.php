<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #2c3e50; color: #ffffff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">Handayani</h1>
        <p style="margin: 5px 0 0;">Reset Password</p>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e0e0e0; border-top: none;">
        <p>Assalamu'alaikum Wr. Wb.</p>

        <p>Anda menerima email ini karena kami menerima permintaan reset password untuk akun Anda.</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}"
               style="background-color: #2c3e50; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; display: inline-block;">
                Reset Password
            </a>
        </div>

        <p style="font-size: 13px; color: #666;">Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:</p>
        <p style="font-size: 12px; color: #888; word-break: break-all;">{{ $resetUrl }}</p>

        <p>Link ini berlaku selama <strong>60 menit</strong>. Jika Anda tidak meminta reset password, abaikan email ini.</p>

        <p>Wassalamu'alaikum Wr. Wb.</p>

        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

        <p style="font-size: 12px; color: #888;">
            Email ini dikirim secara otomatis. Mohon tidak membalas email ini.
        </p>
    </div>
</body>
</html>
