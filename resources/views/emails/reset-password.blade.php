<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            border-bottom: 2px solid #dc3545;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #dc3545;
            margin: 0;
        }
        .button {
            display: inline-block;
            background-color: #dc3545;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #c82333;
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
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 3px;
            margin: 15px 0;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <div class="header">
                <h1>Reset Password Anda</h1>
            </div>

            <p>Halo {{ $user->name }},</p>

            <p>Anda telah meminta untuk mereset password Joglo66 Anda. Klik tombol di bawah untuk melanjutkan:</p>

            <center>
                <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            </center>

            <p>Atau copy link berikut ke browser Anda:</p>
            <p style="word-break: break-all; background-color: #f9f9f9; padding: 10px; border-radius: 3px;">
                {{ $resetUrl }}
            </p>

            <div class="alert">
                <strong>PERHATIAN:</strong> Link reset ini hanya berlaku hingga {{ $expiresAt }} (15 menit). Segera reset password Anda sebelum link expired.
            </div>

            <p>Jika Anda tidak melakukan permintaan reset password ini, abaikan email ini dan password Anda akan tetap aman.</p>

            <div class="footer">
                <p>© 2026 Joglo66 Mini Soccer. All rights reserved.</p>
                <p>Email ini dikirim otomatis, mohon tidak membalas email ini.</p>
            </div>
        </div>
    </div>
</body>
</html>
