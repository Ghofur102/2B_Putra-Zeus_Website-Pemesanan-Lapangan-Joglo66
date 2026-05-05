<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailVerificationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    /**
     * Show forgot password form
     */
    public function showLinkRequestForm()
    {
        return view('tenant.auth.forgot-password');
    }

    /**
     * Send password reset email
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.exists' => 'Email tidak ditemukan dalam sistem',
        ]);

        try {
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                // Don't reveal whether email exists (security best practice)
                return redirect()->back()
                               ->with('status', 'Jika email terdaftar, link reset akan dikirim.');
            }

            // Generate reset token (valid 15 minutes!)
            $token = Str::random(64);
            EmailVerificationToken::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'token' => hash('sha256', $token),
                'type' => 'reset',
                'created_at' => now(),
                'expires_at' => now()->addMinutes(15), // 15 MINUTES!
            ]);

            // Send reset email
            Mail::to($user->email)->send(new ResetPasswordMail($user, $token));

            return redirect()->back()
                           ->with('status', 'Link reset password telah dikirim ke email Anda.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Show reset password form with token
     */
    public function showResetForm($token)
    {
        // Verify token exists
        $hashedToken = hash('sha256', $token);
        $emailToken = EmailVerificationToken::where('token', $hashedToken)
                                            ->where('type', 'reset')
                                            ->valid()
                                            ->first();

        if (!$emailToken) {
            return redirect()->route('password.request')
                           ->with('error', 'Link reset password tidak valid atau sudah kadaluarsa');
        }

        return view('tenant.auth.reset-password', ['token' => $token]);
    }

    /**
     * Reset password
     */
    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password baru harus diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        // Hash and validate token
        $hashedToken = hash('sha256', $validated['token']);
        $emailToken = EmailVerificationToken::where('token', $hashedToken)
                                            ->where('type', 'reset')
                                            ->first();

        // Validate token
        if (!$emailToken) {
            throw ValidationException::withMessages([
                'token' => 'Token reset password tidak valid',
            ]);
        }

        if ($emailToken->isExpired()) {
            $emailToken->delete();
            return redirect()->route('password.request')
                           ->with('error', 'Link reset password sudah kadaluarsa (15 menit)');
        }

        if ($emailToken->isUsed()) {
            return redirect()->route('password.request')
                           ->with('error', 'Link reset password sudah pernah digunakan');
        }

        // Update password
        $user = User::find($emailToken->user_id);
        $user->update(['password' => $validated['password']]);

        // Mark token as used
        $emailToken->markAsUsed();

        // Revoke all existing tokens (user must login again)
        $user->tokens()->delete();

        return redirect()->route('login')
                       ->with('success', 'Password berhasil direset! Silakan login dengan password baru.');
    }
}

