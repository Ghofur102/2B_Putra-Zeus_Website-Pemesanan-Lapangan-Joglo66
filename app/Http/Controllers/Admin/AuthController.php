<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw new HttpException(401, 'Email atau password salah.');
            }

            if ($user->role === 'customer' || $user->role === 'user') {
                throw new HttpException(403, 'Akses ditolak. Aplikasi ini khusus untuk Admin.');
            }

            if ($user->role === 'worker') {
                $hasField = DB::table('field_admins')->where('fk_user_id', $user->id)->exists();
                if (!$hasField) {
                    throw new HttpException(403, 'Akses ditolak. Anda belum ditugaskan untuk menjaga lapangan manapun. Silakan hubungi Owner.');
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $data = [
                'success' => true,
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => $user
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Terjadi kesalahan pada sistem otentikasi.'];
        }

        return response()->json($data, $status);
    }

    public function profile(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $user = $request->user();

            if (!$user) {
                throw new HttpException(404, 'User tidak ditemukan atau belum login.');
            }

            if ($user->role === 'worker') {
                $fields = DB::table('field_admins')
                    ->join('fields', 'field_admins.fk_field_id', '=', 'fields.id')
                    ->where('field_admins.fk_user_id', $user->id)
                    ->pluck('fields.name')
                    ->toArray();

                $user->managed_fields = empty($fields) ? 'Belum ditugaskan ke lapangan' : implode(', ', $fields);
            }

            $data = [
                'success' => true,
                'message' => 'Berhasil mengambil data profil.',
                'data' => $user
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal memuat data profil.'];
        }

        return response()->json($data, $status);
    }

    public function logout(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $request->user()->currentAccessToken()->delete();

            $data = [
                'status' => 'success',
                'message' => 'Logout berhasil.'
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['status' => 'error', 'message' => 'Gagal memproses logout.'];
        }

        return response()->json($data, $status);
    }
}
