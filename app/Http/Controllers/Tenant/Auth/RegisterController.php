<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailVerificationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Mail\VerifyEmailMail;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('tenant.auth.register');
    }

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
            'phone.regex' => 'Format nomor HP tidak valid',
            'phone.unique' => 'Nomor HP sudah terdaftar',
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        try {
            DB::connection('mysql_joglo66_app')->beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'role' => 'tenant',
            ]);

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

            DB::connection('mysql_joglo66_app')->commit();

            Auth::login($user);

            return redirect()->route('verification.notice')
                             ->with('info', 'Registrasi berhasil! Silakan cek email Anda untuk verifikasi.');

        } catch (\Exception $e) {
            DB::connection('mysql_joglo66_app')->rollBack();

            return back()->withInput()->with('error', 'Terjadi kesalahan saat mendaftar: Pastikan layanan email terhubung dengan baik. (' . $e->getMessage() . ')');
        }
    }
}
