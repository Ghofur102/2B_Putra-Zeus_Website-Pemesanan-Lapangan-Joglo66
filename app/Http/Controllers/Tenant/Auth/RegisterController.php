<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailVerificationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * Show the registration form
     */
    public function showRegistrationForm()
    {
        return view('tenant.auth.register');
    }

    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'regex:/^(\+62|0)[0-9]{9,12}$/', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama harus diisi',
            'name.max' => 'Nama maksimal 255 karakter',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'phone.required' => 'Nomor HP harus diisi',
            'phone.regex' => 'Format nomor HP tidak valid (gunakan format 08xx atau +62)',
            'phone.unique' => 'Nomor HP sudah terdaftar',
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        try {
            // Create user with email_verified_at = null
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'], // Auto-hashed via model
                'role' => 'tenant',
            ]);

            // Generate email verification token (valid 24 hours)
            $token = Str::random(64);
            EmailVerificationToken::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'token' => hash('sha256', $token),
                'type' => 'verify',
                'created_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);

            // Send verification email
            Mail::to($user->email)->send(new VerifyEmailMail($user, $token));

            // Authenticate user and redirect to verification notice
            auth()->login($user);

            return redirect()->route('verification.notice')
                           ->with('info', 'Registrasi berhasil! Silakan cek email Anda untuk verifikasi.');
        } catch (\Exception $e) {
            return back()->withInput()
                       ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
