<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class KaryawanController extends Controller
{
    /**
     * DEVELOPER : Danil
     * ROUTE     : GET /api/owner/karyawan
     * MIDDLEWARE: auth:sanctum, role:pemilik
     * PARAMETER : -
     * OUTPUT    : JsonResponse ['success' => bool, 'data' => array]
     */
    public function index(): JsonResponse
    {
        $karyawan = User::whereIn('role', ['worker', 'manager'])
            ->get(['id', 'name', 'email', 'role']);

        return response()->json([
            'success' => true,
            'data' => $karyawan
        ], 200);
    }

    /**
     * DEVELOPER : Danil
     * ROUTE     : POST /api/owner/karyawan
     * MIDDLEWARE: auth:sanctum, role:pemilik
     * PARAMETER : Request $request [nama, email, password, role]
     * OUTPUT    : JsonResponse ['success' => bool, 'message' => string, 'data' => object]
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:60',
            'email' => 'required|email|unique:mysql_joglo66_app.users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:worker,manager',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Karyawan berhasil ditambahkan.',
            'data' => $user
        ], 201);
    }

    /**
     * DEVELOPER : Danil
     * ROUTE     : PUT /api/owner/karyawan/{id}
     * MIDDLEWARE: auth:sanctum, role:pemilik
     * PARAMETER : Request $request [nama, email, role, (optional)password], $id
     * OUTPUT    : JsonResponse ['success' => bool, 'message' => string]
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:60',
            'email' => 'email|unique:mysql_joglo66_app.users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role' => 'in:worker,manager',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'email', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data karyawan berhasil diperbarui.'
        ], 200);
    }

    /**
     * DEVELOPER : Danil
     * ROUTE     : DELETE /api/owner/karyawan/{id}
     * MIDDLEWARE: auth:sanctum, role:pemilik
     * PARAMETER : $id
     * OUTPUT    : JsonResponse ['success' => bool, 'message' => string]
     */
    public function destroy($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $hasRelation = $user->bookings()->exists() ||
                       $user->expenses()->exists() ||
                       $user->fieldAdmin()->exists();

        if ($hasRelation) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak dapat dihapus karena masih memiliki data transaksi.'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Karyawan berhasil dihapus.'
        ], 200);
    }
}
