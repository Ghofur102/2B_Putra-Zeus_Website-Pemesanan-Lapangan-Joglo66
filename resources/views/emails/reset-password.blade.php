@extends('emails.layouts.main')
@section('title', 'Reset Password - Joglo66')

@section('content')
    <h2 style="color: #1e293b; font-size: 20px; margin-top: 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">Reset Password Akun</h2>

    <p style="font-size: 15px; color: #475569;">Halo <strong>{{ $user->name }}</strong>,</p>

    <p style="font-size: 15px; color: #475569;">Anda menerima email ini karena kami menerima permintaan reset password untuk akun Joglo66 Anda. Klik tombol di bawah ini untuk melanjutkan:</p>

    <div style="text-align: center; margin: 35px 0;">
        <a href="{{ $resetUrl }}" style="display: inline-block; background-color: #1a56db; color: #ffffff; padding: 14px 35px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px;">Reset Password</a>
    </div>

    <p style="font-size: 14px; color: #64748b; margin-bottom: 5px;">Jika tombol di atas tidak berfungsi, *copy-paste* tautan berikut ke browser Anda:</p>
    <p style="font-size: 13px; color: #3b82f6; background-color: #eff6ff; padding: 12px; border-radius: 6px; word-break: break-all; border: 1px dashed #bfdbfe;">
        {{ $resetUrl }}
    </p>

    <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin-top: 30px; border-radius: 4px;">
        <p style="margin: 0; color: #991b1b; font-size: 13px;">
            <strong>PERHATIAN:</strong> Tautan ini hanya berlaku hingga <strong>{{ $expiresAt }}</strong> (15 Menit). Segera lakukan reset sebelum waktu habis.
        </p>
    </div>

    <p style="font-size: 14px; color: #64748b; margin-top: 25px;">Jika Anda tidak pernah meminta reset password, abaikan email ini dan akun Anda akan tetap aman.</p>
@endsection
