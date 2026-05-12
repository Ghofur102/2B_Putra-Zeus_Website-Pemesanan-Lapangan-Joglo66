<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailVerificationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\VerifyEmailMail;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Show user profile
     */
    public function show()
    {
        $user = Auth::guard('web')->user();
        return view('tenant.auth.profile.detail', ['user' => $user]);
    }

    /**
     * Update user profile (name, phone, email)
     */
    public function update(Request $request)
    {
        // Memberitahu Code Editor bahwa $user adalah model App\Models\User
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(\+62|0)[0-9]{9,12}$/', 'unique:users,phone,' . $user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ], [
            'name.required' => 'Nama harus diisi',
            'name.max' => 'Nama maksimal 255 karakter',
            'phone.required' => 'Nomor HP harus diisi',
            'phone.regex' => 'Format nomor HP tidak valid',
            'phone.unique' => 'Nomor HP sudah digunakan oleh user lain',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan oleh user lain',
        ]);

        try {
            $emailChanged = $user->email !== $validated['email'];

            // Update basic fields
            $user->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
            ]);

            // Handle email change with reverification
            if ($emailChanged) {
                $user->update([
                    'email' => $validated['email'],
                    'email_verified_at' => null, // Reset verification
                ]);

                // Generate new verification token
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

                return back()->with('warning', 'Email berhasil diubah! Silakan verifikasi email baru Anda. Cek inbox email.');
            }

            return back()->with('success', 'Profil berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        // Memberitahu Code Editor bahwa $user adalah model App\Models\User
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini harus diisi',
            'password.required' => 'Password baru harus diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        // Verify current password
        if (!password_verify($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak sesuai',
            ]);
        }

        // Check if new password same as old
        if (password_verify($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password baru tidak boleh sama dengan password lama',
            ]);
        }

        try {
            // Update password
            $user->update(['password' => $validated['password']]);

            // Revoke all tokens and force re-login
            $user->tokens()->delete();

            // Logout user
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('login')
                           ->with('success', 'Password berhasil diubah! Silakan login kembali.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
