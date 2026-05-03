<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Joglo66</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            background: transparent;
            padding: 20px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .navbar-logo {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #4A69A1 0%, #4C74C9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(74, 105, 161, 0.15);
        }

        .navbar-menu {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .navbar-menu a {
            color: #333;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: color 0.3s;
        }

        .navbar-menu a:hover {
            color: #4A69A1;
        }

        .navbar-btn {
            background: #4A69A1;
            color: white;
            border: none;
            padding: 10px 28px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .navbar-btn:hover {
            background: #3d5585;
        }

        /* ==================== MAIN CONTENT ==================== */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        /* ==================== REGISTER CONTAINER ==================== */
        .register-container {
            background: white;
            border: 1px solid #E0E0E0;
            border-radius: 45px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 1000px;
            display: flex;
            min-height: 550px;
            overflow: hidden;
        }

        /* ==================== WELCOME SECTION (LEFT) ==================== */
        .welcome-section {
            flex: 1;
            background: linear-gradient(135deg, #4A69A1 0%, #4C74C9 100%);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            border-radius: 45px 0 0 45px;
        }

        .welcome-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 25px;
            letter-spacing: 0.5px;
            opacity: 0.95;
        }

        .welcome-logo {
            width: 240px;
            height: 240px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 80px;
            font-weight: 700;
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .welcome-description {
            font-size: 15px;
            line-height: 1.6;
            max-width: 280px;
            opacity: 0.9;
            font-weight: 400;
        }

        /* ==================== FORM SECTION (RIGHT) ==================== */
        .form-section {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-title {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 35px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 13px;
            letter-spacing: 0.3px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #D8D8D8;
            border-radius: 12px;
            font-size: 14px;
            background: #FAFAFA;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .form-group input:focus {
            outline: none;
            border-color: #4A69A1;
            background: white;
            box-shadow: 0 4px 16px rgba(74, 105, 161, 0.12);
        }

        .form-group input::placeholder {
            color: #AAA;
        }

        .form-group.error input {
            border-color: #dc3545;
            background: #fff8f8;
        }

        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 6px;
            font-weight: 500;
        }

        /* ==================== BUTTON ==================== */
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4A69A1 0%, #4C74C9 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 25px;
            box-shadow: 0 4px 16px rgba(74, 105, 161, 0.2);
            font-family: 'Poppins', sans-serif;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(74, 105, 161, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* ==================== DIVIDER & LINK ==================== */
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            gap: 12px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #D8D8D8;
        }

        .divider-text {
            color: #999;
            font-size: 13px;
            font-weight: 500;
        }

        .google-btn {
            width: 100%;
            padding: 12px;
            background: #F5F5F5;
            border: 1px solid #D8D8D8;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #333;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .google-btn:hover {
            background: #ECECEC;
            border-color: #BBB;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #4A69A1;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .login-link a:hover {
            color: #3d5585;
        }

        /* ==================== ALERTS ==================== */
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            animation: slideDown 0.3s ease;
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

        .alert-danger {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 30px;
            }

            .navbar-menu {
                gap: 20px;
            }

            .navbar-menu a {
                font-size: 12px;
            }

            .navbar-btn {
                padding: 8px 20px;
                font-size: 12px;
            }

            .main-content {
                padding: 30px 15px;
            }

            .register-container {
                flex-direction: column;
                min-height: auto;
            }

            .welcome-section {
                border-radius: 45px 45px 0 0;
                padding: 40px 30px;
                min-height: 250px;
            }

            .welcome-logo {
                width: 140px;
                height: 140px;
                font-size: 50px;
                margin-bottom: 15px;
            }

            .welcome-title {
                font-size: 14px;
                margin-bottom: 15px;
            }

            .welcome-description {
                font-size: 13px;
                max-width: 100%;
            }

            .form-section {
                border-radius: 0 0 45px 45px;
                padding: 40px 30px;
            }

            .form-title {
                font-size: 24px;
                margin-bottom: 25px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-group input {
                padding: 12px 14px;
                font-size: 13px;
            }

            .submit-btn {
                padding: 12px;
                font-size: 14px;
                margin-top: 15px;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                padding: 12px 15px;
            }

            .navbar-logo {
                width: 45px;
                height: 45px;
                font-size: 16px;
            }

            .navbar-menu {
                gap: 10px;
            }

            .navbar-menu a {
                font-size: 11px;
            }

            .register-container {
                border-radius: 25px;
            }

            .welcome-section,
            .form-section {
                border-radius: 0;
                padding: 30px 20px;
            }

            .welcome-section {
                border-radius: 25px 25px 0 0;
            }

            .form-section {
                border-radius: 0 0 25px 25px;
            }

            .form-title {
                font-size: 20px;
                margin-bottom: 20px;
            }

            .welcome-logo {
                width: 120px;
                height: 120px;
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <div class="navbar">
        <div class="navbar-logo">J</div>
        <div class="navbar-menu">
            <a href="#kontak">Kontak Kami</a>
            <a href="{{ route('login') }}">Masuk</a>
            <a href="{{ route('register') }}" class="navbar-btn">Daftar</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="register-container">
            <!-- WELCOME SECTION -->
            <div class="welcome-section">
                <div class="welcome-title">Selamat Datang Di</div>
                <div class="welcome-logo">⚽</div>
                <div class="welcome-description">
                    Platform booking lapangan olahraga terpercaya dengan layanan terbaik untuk Anda
                </div>
            </div>

            <!-- FORM SECTION -->
            <div class="form-section">
                <div class="form-title">Daftar</div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Registrasi Gagal!</strong><br>
                        @foreach ($errors->all() as $error)
                            • {{ $error }}<br>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('register') }}" method="POST">
                    @csrf

                    <div class="form-group @error('name') error @enderror">
                        <label for="name">Nama Lengkap</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Masukkan nama lengkap Anda" required>
                        @error('name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group @error('email') error @enderror">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email Anda" required>
                        @error('email')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group @error('phone') error @enderror">
                        <label for="phone">Nomor HP</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Contoh: 08123456789" required>
                        @error('phone')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group @error('password') error @enderror">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" required>
                        @error('password')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group @error('password_confirmation') error @enderror">
                        <label for="password_confirmation">Konfirmasi Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Ketik ulang password" required>
                        @error('password_confirmation')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="submit-btn">Daftar Sekarang</button>
                </form>

                <div class="divider">
                    <span class="divider-text">Atau</span>
                </div>

                <button class="google-btn">
                    <span>🔵</span>
                    Daftar dengan Google
                </button>

                <div class="login-link">
                    Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
