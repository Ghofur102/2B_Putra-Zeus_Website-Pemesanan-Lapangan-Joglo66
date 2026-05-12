<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Cek Kredensial Email & Password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.'
            ], 401);
        }

        // ====================================================================
        // 2. VALIDASI HAK AKSES LOGIN (ROLE & FIELD_ADMINS)
        // ====================================================================

        // A. Tolak jika yang login adalah Customer/Penyewa biasa
        if ($user->role === 'customer' || $user->role === 'user') { // Sesuaikan dengan nama role penyewa Anda
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Aplikasi ini khusus untuk Admin.'
            ], 403); // 403 Forbidden
        }

        // B. Jika dia adalah Worker, wajib punya lapangan di tabel field_admins
        if ($user->role === 'worker') {
            $hasField = DB::table('field_admins')->where('fk_user_id', $user->id)->exists();

            if (!$hasField) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Anda belum ditugaskan untuk menjaga lapangan manapun. Silakan hubungi Owner.'
                ], 403);
            }
        }
        // ====================================================================

        // 3. Jika lolos semua validasi di atas, buatkan Token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $user
        ], 200);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan atau belum login.',
            ], 404);
        }

        // --- TAMBAHAN LOGIKA UNTUK WORKER ---
        if ($user->role === 'worker') {
            // Cari nama lapangan yang dijaga oleh worker ini melalui join tabel
            $fields = \Illuminate\Support\Facades\DB::table('field_admins')
                ->join('fields', 'field_admins.fk_field_id', '=', 'fields.id')
                ->where('field_admins.fk_user_id', $user->id)
                ->pluck('fields.name')
                ->toArray();

            // Sisipkan data lapangan ke dalam response user
            // Jika dia menjaga lebih dari 1 lapangan, namanya akan digabung dengan koma
            $user->managed_fields = empty($fields) ? 'Belum ditugaskan ke lapangan' : implode(', ', $fields);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data profil.',
            'data' => $user
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.'
        ], 200);
    }
}
