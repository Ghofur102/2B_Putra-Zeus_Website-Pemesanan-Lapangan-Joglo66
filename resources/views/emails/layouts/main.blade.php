<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f4f7f6; margin: 0; padding: 20px;">

    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">

        <tr>
            <td style="background-color: #1a56db; padding: 30px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 1px;">JOGLO66</h1>
                <p style="color: #caddff; margin: 5px 0 0 0; font-size: 14px;">Mini Soccer Arena</p>
            </td>
        </tr>

        <tr>
            <td style="padding: 40px 30px;">
                @yield('content')
            </td>
        </tr>

        <tr>
            <td style="background-color: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                <p style="margin: 0; color: #64748b; font-size: 12px;">&copy; {{ date('Y') }} Joglo66 Mini Soccer. All rights reserved.</p>
                <p style="margin: 5px 0 0 0; color: #94a3b8; font-size: 11px;">Email ini dikirim secara otomatis. Mohon tidak membalas pesan ini.</p>
            </td>
        </tr>
    </table>

</body>
</html>
