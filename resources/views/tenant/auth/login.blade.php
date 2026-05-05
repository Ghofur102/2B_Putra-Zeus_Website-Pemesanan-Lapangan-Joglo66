<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Joglo66</title>
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
            background: linear-gradient(135deg, #4C7ED9 0%, #4E82D9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(76, 126, 217, 0.15);
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
            color: #4C7ED9;
        }

        .navbar-menu a.active {
            background: #4C7ED9;
            color: white;
            padding: 10px 28px;
            border-radius: 25px;
            transition: background 0.3s;
        }

        .navbar-menu a.active:hover {
            background: #3d5fa3;
        }

        /* ==================== MAIN CONTENT ==================== */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        /* ==================== LOGIN CONTAINER ==================== */
        .login-container {
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
            background: linear-gradient(135deg, #49679F 0%, #4E7DD9 100%);
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

        .form-group input[type="email"],
        .form-group input[type="password"] {
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
            border-color: #4C7ED9;
            background: white;
            box-shadow: 0 4px 16px rgba(76, 126, 217, 0.12);
        }

        .form-group input::placeholder {
            color: #AAA;
        }

        /* ==================== CHECKBOX & FORGOT PASSWORD ==================== */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0 25px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #4C7ED9;
        }

        .checkbox-group label {
            margin: 0;
            font-size: 13px;
            color: #555;
            font-weight: 500;
            cursor: pointer;
        }

        .forgot-password a {
            color: #4C7ED9;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.3s;
        }

        .forgot-password a:hover {
            color: #3d5fa3;
            text-decoration: underline;
        }

        /* ==================== BUTTON ==================== */
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4C7ED9 0%, #4E82D9 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(76, 126, 217, 0.2);
            font-family: 'Poppins', sans-serif;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(76, 126, 217, 0.3);
        }

        .login-btn:active {
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

        .register-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #666;
        }

        .register-link a {
            color: #4C7ED9;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .register-link a:hover {
            color: #3d5fa3;
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

        .alert-warning {
            background-color: #fffacd;
            border: 1px solid #ffd700;
            color: #8B7500;
        }

        .alert-success {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
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

            .navbar-menu a.active {
                padding: 8px 20px;
                font-size: 12px;
            }

            .main-content {
                padding: 30px 15px;
            }

            .login-container {
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

            .form-options {
                margin: 15px 0 20px;
            }

            .login-btn {
                padding: 12px;
                font-size: 14px;
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

            .navbar-menu a.active {
                padding: 6px 16px;
                font-size: 11px;
            }

            .login-container {
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

            .form-options {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .forgot-password {
                width: 100%;
                text-align: left;
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
            <a href="{{ route('login') }}" class="active">Masuk</a>
            <a href="{{ route('register') }}">Daftar</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="login-container">
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
                <div class="form-title">Login</div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Login Gagal!</strong><br>
                        @foreach ($errors->all() as $error)
                            • {{ $error }}<br>
                        @endforeach
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert alert-warning">
                        ⚠ {{ session('warning') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success">
                        ✓ {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email Anda" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
                    </div>

                    <div class="form-options">
                        <div class="checkbox-group">
                            <input type="checkbox" id="remember_me" name="remember_me" value="1">
                            <label for="remember_me">Ingat saya</label>
                        </div>
                        <div class="forgot-password">
                            <a href="{{ route('password.request') }}">Lupa password?</a>
                        </div>
                    </div>

                    <button type="submit" class="login-btn">Masuk</button>
                </form>

                <div class="divider">
                    <span class="divider-text">Belum punya akun?</span>
                </div>

                <div class="register-link">
                    <a href="{{ route('register') }}">Daftar di sini</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
