<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Joglo66</title>
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
            background: white;
            padding: 20px 60px;
            display: flex;
            justify-content: space-between;
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

        .navbar-menu {
            display: flex;
            gap: 50px;
            align-items: center;
        }

        .navbar-menu a {
            color: #333;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: color 0.3s;
            position: relative;
        }

        .navbar-menu a:hover {
            color: #4C7ED9;
        }

        .navbar-menu a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #4C7ED9;
            transition: width 0.3s;
        }

        .navbar-menu a:hover::after {
            width: 100%;
        }

        .navbar-profile {
            font-weight: 700;
            font-size: 16px;
            color: #000;
            padding: 8px 20px;
            background: #E8F0FF;
            border-radius: 20px;
        }

        /* ==================== MAIN CONTENT ==================== */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 60px 20px 50px;
        }

        /* ==================== PROFILE CONTAINER ==================== */
        .profile-container {
            background: white;
            border: 1px solid #E0E0E0;
            border-radius: 45px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 1100px;
            position: relative;
            overflow: visible;
            margin-top: 20px;
        }

        /* ==================== PROFILE BADGE ==================== */
        .profile-badge {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            background: #4C7ED9;
            color: black;
            padding: 10px 40px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 16px;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(76, 126, 217, 0.2);
        }

        /* ==================== MAIN PANEL ==================== */
        .main-panel {
            background: linear-gradient(135deg, #49679F 0%, #4E7DD9 100%);
            padding: 80px 60px 60px;
            border-radius: 45px;
            min-height: 600px;
            display: flex;
            gap: 60px;
        }

        /* ==================== FORM SECTION ==================== */
        .form-section {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-section h2 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: white;
            font-weight: 500;
            font-size: 13px;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.15);
            font-family: 'Poppins', sans-serif;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        .error-message {
            color: #FFB6C1;
            font-size: 12px;
            margin-top: 6px;
            font-weight: 500;
        }

        /* ==================== DIVIDER ==================== */
        .form-divider {
            width: 1px;
            background: rgba(255, 255, 255, 0.2);
            margin: 0 30px;
        }

        /* ==================== BUTTONS ==================== */
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 32px;
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

        .btn-logout {
            background: #E74C3C;
            color: white;
        }

        .btn-logout:hover {
            background: #C0392B;
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

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1024px) {
            .main-panel {
                padding: 80px 40px 60px;
                gap: 40px;
            }

            .navbar {
                padding: 15px 40px;
            }

            .navbar-menu {
                gap: 30px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 25px;
            }

            .navbar-logo {
                font-size: 18px;
            }

            .navbar-menu {
                gap: 20px;
                font-size: 12px;
            }

            .navbar-profile {
                font-size: 14px;
                padding: 6px 16px;
            }

            .main-content {
                padding: 40px 15px;
            }

            .profile-container {
                border-radius: 30px;
            }

            .profile-badge {
                font-size: 14px;
                padding: 8px 32px;
                top: -18px;
            }

            .main-panel {
                flex-direction: column;
                padding: 70px 30px 40px;
                gap: 30px;
                min-height: auto;
                border-radius: 30px;
            }

            .form-divider {
                display: none;
            }

            .form-section h2 {
                font-size: 18px;
                margin-bottom: 20px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-group input,
            .form-group textarea {
                padding: 12px 14px;
                font-size: 13px;
            }

            .button-group {
                margin-top: 20px;
            }

            .btn {
                padding: 10px 24px;
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
                flex-wrap: wrap;
                gap: 10px;
            }

            .navbar-logo {
                font-size: 16px;
                flex: 1;
            }

            .navbar-menu {
                width: 100%;
                gap: 8px;
                font-size: 11px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .navbar-profile {
                width: 100%;
                text-align: center;
                font-size: 13px;
            }

            .main-content {
                padding: 30px 10px;
            }

            .profile-container {
                border-radius: 20px;
            }

            .profile-badge {
                font-size: 12px;
                padding: 6px 24px;
                top: -16px;
            }

            .main-panel {
                padding: 60px 20px 30px;
                border-radius: 20px;
                min-height: auto;
            }

            .form-section h2 {
                font-size: 16px;
                margin-bottom: 18px;
            }

            .form-group {
                margin-bottom: 12px;
            }

            .button-group {
                flex-direction: column;
                gap: 10px;
            }

            .btn {
                width: 100%;
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
        <div class="navbar-menu">
            <a href="#booking">Booking Lapangan</a>
            <a href="#jadwal">Jadwal Turnament</a>
            <a href="#kontak">Kontak Kami</a>
        </div>
        <div class="navbar-profile">Profil</div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        @if (session('success'))
            <div class="alert alert-success">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning">
                ⚠ {{ session('warning') }}
            </div>
        @endif

        <div class="profile-container">
            <div class="profile-badge">Profil Saya</div>

            <div class="main-panel">
                <!-- LEFT SECTION: EDIT PROFIL -->
                <div class="form-section">
                    <h2>Edit Profil</h2>

                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="name">Nama Lengkap</label>
                            <input type="text" id="name" name="name" value="{{ $user->name }}" required>
                            @if ($errors->has('name'))
                                <div class="error-message">{{ $errors->first('name') }}</div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="{{ $user->email }}" required>
                            @if ($errors->has('email'))
                                <div class="error-message">{{ $errors->first('email') }}</div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="phone">Nomor HP</label>
                            <input type="tel" id="phone" name="phone" value="{{ $user->phone }}" required>
                            @if ($errors->has('phone'))
                                <div class="error-message">{{ $errors->first('phone') }}</div>
                            @endif
                        </div>

                        <div class="button-group">
                            <button type="submit" class="btn">Simpan Perubahan</button>
                            <button type="reset" class="btn btn-secondary">Batal</button>
                        </div>
                    </form>
                </div>

                <!-- DIVIDER -->
                <div class="form-divider"></div>

                <!-- RIGHT SECTION: UBAH PASSWORD -->
                <div class="form-section">
                    <h2>Ubah Password</h2>

                    <form action="{{ route('password.change') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="current_password">Password Saat Ini</label>
                            <input type="password" id="current_password" name="current_password" required>
                            @if ($errors->has('current_password'))
                                <div class="error-message">{{ $errors->first('current_password') }}</div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="password">Password Baru</label>
                            <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" required>
                            @if ($errors->has('password'))
                                <div class="error-message">{{ $errors->first('password') }}</div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Konfirmasi Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required>
                        </div>

                        <div class="button-group">
                            <button type="submit" class="btn">Ubah Password</button>
                            <button type="reset" class="btn btn-secondary">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- LOGOUT BUTTON -->
    <div style="text-align: center; padding: 30px 20px;">
        <form action="{{ route('logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-logout" style="margin-top: 20px;">🚪 Logout</button>
        </form>
    </div>
</body>
</html>
