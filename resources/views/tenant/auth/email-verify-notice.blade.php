<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - Joglo66</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #ECECEC;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ==================== NAVBAR ==================== */
        .navbar {
            background: white;
            padding: 20px 60px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .navbar-logo {
            font-weight: 700;
            font-size: 24px;
            color: #000;
            letter-spacing: -1px;
        }

        /* ==================== MAIN CONTENT ==================== */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 60px 20px 50px;
        }

        /* ==================== VERIFICATION CONTAINER ==================== */
        .verification-container {
            background: white;
            border: 1px solid #E0E0E0;
            border-radius: 45px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 700px;
            position: relative;
            overflow: visible;
            margin-top: 20px;
        }

        /* ==================== MAIN PANEL ==================== */
        .main-panel {
            background: linear-gradient(135deg, #49679F 0%, #4E7DD9 100%);
            padding: 80px 60px;
            border-radius: 45px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            text-align: center;
        }

        /* ==================== EMAIL ICON ==================== */
        .email-icon {
            font-size: 80px;
            line-height: 1;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        /* ==================== VERIFICATION MESSAGE ==================== */
        .verification-message {
            color: white;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.6;
            max-width: 500px;
        }

        .verification-message strong {
            font-weight: 700;
            display: block;
            margin-bottom: 10px;
        }

        /* ==================== BUTTON GROUP ==================== */
        .button-group {
            display: flex;
            gap: 20px;
            width: 100%;
            margin-top: 20px;
            justify-content: center;
        }

        .btn {
            padding: 12px 40px;
            background: #4C7ED9;
            color: black;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(76, 126, 217, 0.2);
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 126, 217, 0.3);
            background: #3d5fa3;
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        /* ==================== ALERTS ==================== */
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            animation: slideDown 0.3s ease;
            position: absolute;
            top: 40px;
            left: 60px;
            right: 60px;
            z-index: 15;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
        }

        .alert-warning {
            background-color: #fffacd;
            border: 1px solid #ffd700;
            color: #8B7500;
        }

        .alert-info {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            color: #1565c0;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1024px) {
            .main-panel {
                padding: 60px 40px;
                gap: 25px;
            }

            .navbar {
                padding: 15px 40px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 25px;
            }

            .navbar-logo {
                font-size: 18px;
            }

            .main-content {
                padding: 40px 15px;
            }

            .verification-container {
                border-radius: 30px;
            }

            .main-panel {
                padding: 50px 30px;
                border-radius: 30px;
                gap: 20px;
            }

            .email-icon {
                font-size: 60px;
            }

            .verification-message {
                font-size: 16px;
            }

            .button-group {
                gap: 15px;
            }

            .btn {
                padding: 10px 30px;
                font-size: 13px;
                flex: 1;
            }

            .alert {
                left: 30px;
                right: 30px;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                padding: 12px 15px;
            }

            .navbar-logo {
                font-size: 16px;
            }

            .main-content {
                padding: 30px 10px;
            }

            .verification-container {
                border-radius: 20px;
            }

            .main-panel {
                padding: 40px 20px;
                border-radius: 20px;
                gap: 20px;
            }

            .email-icon {
                font-size: 50px;
            }

            .verification-message {
                font-size: 15px;
            }

            .button-group {
                flex-direction: column;
                gap: 12px;
            }

            .btn {
                width: 100%;
                padding: 12px 24px;
                font-size: 13px;
            }

            .alert {
                left: 15px;
                right: 15px;
                font-size: 12px;
                padding: 12px 14px;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <div class="navbar">
        <div class="navbar-logo">LOGO</div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        @if (session('success'))
            <div class="alert alert-success">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info">
                ℹ {{ session('info') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning">
                ⚠ {{ session('warning') }}
            </div>
        @endif

        <div class="verification-container">
            <div class="main-panel">
                <!-- EMAIL ICON -->
                <div class="email-icon">📧</div>

                <!-- VERIFICATION MESSAGE -->
                <div class="verification-message">
                    <strong>Silakan Verifikasi Email Anda</strong>
                    Kami telah mengirim tautan verifikasi ke email Anda. Silakan cek kotak masuk atau folder spam untuk memverifikasi akun Anda dan melanjutkan.
                </div>

                <!-- BUTTONS -->
                <div class="button-group">
                    <a href="{{ route('login') }}" class="btn btn-secondary">← Kembali</a>
                    <form action="{{ route('verification.send') }}" method="POST" style="display: inline; width: 100%;">
                        @csrf
                        <button type="submit" class="btn" style="width: 100%;">↻ Kirim Ulang Email</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
