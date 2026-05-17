<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailVerificationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Mail\VerifyEmailMail;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('tenant.auth.login');
    }

    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'       => ['required', 'string', 'email'],
            'password'    => ['required', 'string'],
            'remember_me' => ['nullable', 'boolean'],
        ]);

        // Gunakan Auth::attempt() — ini yang benar untuk 'hashed' cast
        $credentials = [
            'email'    => $validated['email'],
            'password' => $validated['password'],
        ];

        // Cek kredensial TANPA login dulu
        if (!Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai',
            ]);
        }

        // Ambil user setelah kredensial valid
        $user = User::where('email', $validated['email'])->first();

        // Cek verifikasi email
        if ($user->email_verified_at === null) {
            return redirect()->route('login')
                ->with('warning', 'Email Anda belum diverifikasi. Silakan cek inbox email.');
        }

        // Login
        Auth::login($user, $validated['remember_me'] ?? false);

        return redirect()->route('profile.show')
            ->with('success', 'Login berhasil!');
    }

    /**
     * Handle user logout
     */
    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Logout berhasil');
    }

    /**
     * Verify email via token link
     */
    public function verifyEmail($token)
    {
        // Hash the token
        $hashedToken = hash('sha256', $token);

        // Find token in database (explicit connection)
        $emailToken = EmailVerificationToken::where('token', $hashedToken)
            ->where('type', 'verify')
            ->first();

        // Validate token
        if (!$emailToken) {
            return redirect()->route('login')
                ->with('error', 'Token verifikasi tidak valid');
        }

        if ($emailToken->isExpired()) {
            $emailToken->delete();
            return redirect()->route('login')
                ->with('error', 'Token verifikasi sudah kadaluarsa (24 jam)');
        }

        if ($emailToken->isUsed()) {
            return redirect()->route('login')
                ->with('error', 'Token verifikasi sudah pernah digunakan');
        }

        // Mark email as verified
        $user = User::find($emailToken->user_id);

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'User tidak ditemukan');
        }

        // Update email_verified_at
        $updateResult = $user->update(['email_verified_at' => now()]);

        // Verify the update actually saved to database
        $verifiedUser = $user->fresh();

        if (!$verifiedUser->email_verified_at) {
            Log::error('Email verification failed to persist', [
                'user_id' => $user->id,
                'email' => $user->email,
                'update_result' => $updateResult,
                'verified_user_email_verified_at' => $verifiedUser->email_verified_at,
            ]);

            return redirect()->route('login')
                ->with('error', 'Verifikasi gagal: email tidak tersimpan di database');
        }

        // Mark token as used
        $emailToken->markAsUsed();

        // Log successful verification
        Log::info('Email verification successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'email_verified_at' => $verifiedUser->email_verified_at,
            'connection' => $user->getConnectionName(),
        ]);

        return redirect()->route('login')
            ->with('success', 'Email berhasil diverifikasi! Silakan login sekarang.');
    }

    /**
     * Show email verification notice page
     */
    public function showVerificationNotice()
    {
        $user = Auth::guard('web')->user();

        // If user already verified, redirect to profile
        if ($user && $user->email_verified_at !== null) {
            return redirect()->route('profile.show');
        }

        // If user not authenticated, redirect to login
        if (!$user) {
            return redirect()->route('login');
        }

        return view('tenant.auth.email-verify-notice');
    }

    /**
     * Send verification email to user
     */
    public function sendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->email_verified_at !== null) {
            return redirect()->route('profile.show')
                ->with('info', 'Email Anda sudah terverifikasi.');
        }

        $response = redirect()->route('verification.notice');

        try {
            EmailVerificationToken::where('user_id', $user->id)
                ->where('type', 'verify')
                ->delete();

            $token = Str::random(64);
            EmailVerificationToken::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'token' => hash('sha256', $token),
                'type' => 'verify',
                'created_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);

            Mail::to($user->email)->send(new VerifyEmailMail($user, $token));

            $response = $response->with('success', 'Email verifikasi telah dikirim ulang. Silakan cek inbox atau folder spam Anda.');
        } catch (\Exception $e) {
            $response = $response->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }

        return $response;
    }
}
