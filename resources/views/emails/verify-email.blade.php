<!DOCTYPE html>
<html lang="id" xml:lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - Joglo66</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .content {
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            border-radius: 3px;
            margin: 15px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <div class="header">
                <h1>Verifikasi Email Anda</h1>
            </div>

            <p>Halo {{ $user->name }},</p>

            <p>Terima kasih telah mendaftar di Joglo66! Silakan verifikasi email Anda dengan mengklik tombol di bawah:</p>

            <div style="text-align: center;">
                <a href="{{ $verifyUrl }}" class="button">Verifikasi Email</a>
            </div>

            <p>Atau copy link berikut ke browser Anda:</p>
            <p style="word-break: break-all; background-color: #f9f9f9; padding: 10px; border-radius: 3px;">
                {{ $verifyUrl }}
            </p>

            <div class="alert">
                <strong>Catatan:</strong> Link verifikasi ini akan berlaku hingga {{ $expiresAt }} (24 jam). Jika link sudah expired, silakan daftar kembali.
            </div>

            <p>Jika Anda tidak melakukan pendaftaran ini, silakan abaikan email ini.</p>

            <div class="footer">
                <p>© 2026 Joglo66 Mini Soccer. All rights reserved.</p>
                <p>Email ini dikirim otomatis, mohon tidak membalas email ini.</p>
            </div>
        </div>
    </div>
</body>
</html>
