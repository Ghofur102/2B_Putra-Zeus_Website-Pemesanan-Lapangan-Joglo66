@extends('emails.layouts.main')
@section('title', 'Verifikasi Email - Joglo66')

@section('content')
    <h2 style="color: #1e293b; font-size: 20px; margin-top: 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">Verifikasi Email Anda</h2>

    <p style="font-size: 15px; color: #475569;">Halo <strong>{{ $user->name }}</strong>,</p>

    <p style="font-size: 15px; color: #475569;">Terima kasih telah mendaftar di Website <strong>Joglo66</strong>! Tinggal satu langkah lagi untuk menyelesaikan pendaftaran. Silakan klik tombol di bawah ini untuk memverifikasi alamat email Anda:</p>

    <div style="text-align: center; margin: 35px 0;">
        <a href="{{ $verifyUrl }}" style="display: inline-block; background-color: #10b981; color: #ffffff; padding: 14px 35px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px;">Verifikasi Email Saya</a>
    </div>

    <p style="font-size: 14px; color: #64748b; margin-bottom: 5px;">Jika tombol di atas tidak berfungsi, *copy-paste* tautan berikut ke browser Anda:</p>
    <p style="font-size: 13px; color: #3b82f6; background-color: #eff6ff; padding: 12px; border-radius: 6px; word-break: break-all; border: 1px dashed #bfdbfe;">
        {{ $verifyUrl }}
    </p>

    <div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; margin-top: 30px; border-radius: 4px;">
        <p style="margin: 0; color: #92400e; font-size: 13px;">
            <strong>CATATAN:</strong> Tautan verifikasi ini akan kedaluwarsa pada <strong>{{ $expiresAt }}</strong>. Jika sudah kedaluwarsa, Anda perlu mendaftar ulang.
        </p>
    </div>

    <p style="font-size: 14px; color: #64748b; margin-top: 25px;">Jika Anda merasa tidak pernah mendaftar di Joglo66, Anda dapat mengabaikan email ini.</p>
@endsection
