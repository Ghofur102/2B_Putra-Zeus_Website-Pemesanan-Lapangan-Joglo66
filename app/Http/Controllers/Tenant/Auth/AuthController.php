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
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use UnexpectedValueException;
use Throwable;

class AuthController extends Controller
{
    private const ROUTE_LOGIN = 'login';
    private const ROUTE_PROFILE = 'profile.show';
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';
    private const STATUS_WARNING = 'warning';
    private const TYPE_VERIFY = 'verify';

    /**
     * Show login form
     */
    public function showLoginForm(): View
    {
        return view('tenant.auth.login');
    }

    /**
     * Handle user login
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'       => ['required', 'string', 'email'],
            'password'    => ['required', 'string'],
            'remember_me' => ['nullable', 'boolean'],
        ]);

        if (!Auth::validate(['email' => $validated['email'], 'password' => $validated['password']])) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai',
            ]);
        }

        $user = User::where('email', $validated['email'])->first();
        $response = redirect()->route(self::ROUTE_PROFILE)->with(self::STATUS_SUCCESS, 'Login berhasil!');

        if ($user->email_verified_at === null) {
            $response = redirect()->route(self::ROUTE_LOGIN)->with(self::STATUS_WARNING, 'Email Anda belum diverifikasi. Silakan cek inbox email.');
        } else {
            Auth::login($user, $validated['remember_me'] ?? false);
            $request->session()->regenerate();

            if ($user->role === 'tenant') {
                $response = redirect()->route('tenant.booking.dashboard')->with(self::STATUS_SUCCESS, 'Login berhasil!');
            } elseif (in_array($user->role, ['manager', 'owner', 'worker', 'treasurer'], true)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $response = redirect()->route(self::ROUTE_LOGIN)->with(self::STATUS_ERROR, 'Akses ditolak! Halaman ini khusus untuk Penyewa.');
            }
        }

        return $response;
    }

    /**
     * Handle user logout
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();
        session()?->invalidate();
        session()?->regenerateToken();

        return redirect()->route(self::ROUTE_LOGIN)->with(self::STATUS_SUCCESS, 'Logout berhasil');
    }

    /**
     * Verify email via token link
     */
    public function verifyEmail($token): RedirectResponse
    {
        $status = self::STATUS_ERROR;
        try {
            $emailToken = $this->getAndValidateToken($token);
            $user = User::find($emailToken->user_id);

            if (!$user) {
                throw new UnexpectedValueException('User tidak ditemukan');
            }

            $this->persistUserVerification($user);
            $emailToken->markAsUsed();

            $message = 'Email berhasil diverifikasi! Silakan login sekarang.';
            $status = self::STATUS_SUCCESS;
        } catch (Throwable $e) {
            $message = $e->getMessage();
        }

        return redirect()->route(self::ROUTE_LOGIN)->with($status, $message);
    }

    /**
     * Show email verification notice page
     */
    public function showVerificationNotice(): RedirectResponse|View
    {
        $user = Auth::guard('web')->user();
        $response = view('tenant.auth.email-verify-notice');

        if (!$user) {
            $response = redirect()->route(self::ROUTE_LOGIN);
        } elseif ($user->email_verified_at !== null) {
            $response = redirect()->route(self::ROUTE_PROFILE);
        }

        return $response;
    }

    /**
     * Send verification email to user
     */
    public function sendVerificationEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            $response = redirect()->route(self::ROUTE_LOGIN);
        } elseif ($user->email_verified_at !== null) {
            $response = redirect()->route(self::ROUTE_PROFILE)->with('info', 'Email Anda sudah terverifikasi.');
        } else {
            $response = redirect()->route('verification.notice');
            try {
                EmailVerificationToken::where('user_id', $user->id)
                    ->where('type', self::TYPE_VERIFY)
                    ->delete();

                $token = Str::random(64);
                EmailVerificationToken::create([
                    'user_id'    => $user->id,
                    'email'      => $user->email,
                    'token'      => hash('sha256', $token),
                    'type'       => self::TYPE_VERIFY,
                    'created_at' => now(),
                    'expires_at' => now()->addHours(24),
                ]);

                Mail::to($user->email)->send(new VerifyEmailMail($user, $token));

                $response = $response->with(self::STATUS_SUCCESS, 'Email verifikasi telah dikirim ulang. Silakan cek inbox atau folder spam Anda.');
            } catch (Throwable $e) {
                $response = $response->with(self::STATUS_ERROR, 'Gagal mengirim email: ' . $e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Private Helper: Mengambil dan memvalidasi token menggunakan Specialized Exception (php:S112)
     */
    private function getAndValidateToken(string $token): EmailVerificationToken
    {
        $emailToken = EmailVerificationToken::where('token', hash('sha256', $token))
            ->where('type', self::TYPE_VERIFY)
            ->first();

        if (!$emailToken) {
            throw new InvalidArgumentException('Token verifikasi tidak valid');
        }

        if ($emailToken->isExpired()) {
            $emailToken->delete();
            throw new UnexpectedValueException('Token verifikasi sudah kadaluarsa (24 jam)');
        }

        if ($emailToken->isUsed()) {
            throw new UnexpectedValueException('Token verifikasi sudah pernah digunakan');
        }

        return $emailToken;
    }

    /**
     * Private Helper: Menyimpan status verifikasi secara persisten menggunakan Specialized Exception (php:S112)
     */
    private function persistUserVerification(User $user): void
    {
        $user->update(['email_verified_at' => now()]);
        $verifiedUser = $user->fresh();

        if (!$verifiedUser->email_verified_at) {
            Log::error('Email verification failed to persist', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);
            throw new UnexpectedValueException('Verifikasi gagal: email tidak tersimpan di database');
        }

        Log::info('Email verification successful', [
            'user_id'           => $user->id,
            'email'             => $user->email,
            'email_verified_at' => $verifiedUser->email_verified_at,
            'connection'        => $user->getConnectionName(),
        ]);
    }
}
