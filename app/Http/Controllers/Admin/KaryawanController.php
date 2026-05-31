<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KaryawanController extends Controller
{
    /**
     * DEVELOPER : Danil
     * ROUTE     : GET /api/admin/karyawan
     * MIDDLEWARE: auth:sanctum, role:pemilik
     * PARAMETER : -
     * OUTPUT    : JsonResponse ['success' => bool, 'data' => array]
     */
    public function index(): JsonResponse
    {
        // 1. Tarik seluruh list data master karyawan terbaru dari database (id, nama, email, role).
        // 2. Kembalikan response JSON sukses status 200 membawa data array karyawan.

        return response()->json([]);
    }

    /**
     * DEVELOPER : Danil
     * ROUTE     : POST /api/admin/karyawan
     * MIDDLEWARE: auth:sanctum, role:pemilik
     * PARAMETER : Request $request [nama, email, password, role]
     * OUTPUT    : JsonResponse ['success' => bool, 'message' => string, 'data' => object]
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validasi input field wajib (nama, email, password, role). Skenario E1 jika tidak lengkap.
        // 2. Cek apakah email sudah terdaftar di database untuk menghindari redundansi akun. Skenario E2 jika duplikat.
        // 3. Enkripsi password menggunakan bcrypt/hash.
        // 4. Lakukan operasi insert record karyawan baru ke tabel users dengan penentuan hak akses (role).
        // 5. Kembalikan response sukses 201 dengan muatan objek data karyawan yang berhasil disimpan.

        return response()->json([]);
    }

    /**
     * DEVELOPER : Danil
     * ROUTE     : PUT /api/admin/karyawan/{id}
     * MIDDLEWARE: auth:sanctum, role:pemilik
     * PARAMETER : Request $request [nama, email, role, (optional)password], $id
     * OUTPUT    : JsonResponse ['success' => bool, 'message' => string]
     */
    public function update(Request $request, $id): JsonResponse
    {
        // 1. Cari record data karyawan berdasarkan primary key ID. Jika tidak ada, return 404.
        // 2. Validasi kelengkapan data masukan baru.
        // 3. Cek pengecualian email unik jika pengguna merubah alamat emailnya.
        // 4. Eksekusi perintah pembaharuan (update) record data profil beserta role/hak akses sistem.
        // 5. Kembalikan response JSON 200 tanda perubahan berhasil disimpan permanen.

        return response()->json([]);
    }

    /**
     * DEVELOPER : Danil
     * ROUTE     : DELETE /api/admin/karyawan/{id}
     * MIDDLEWARE: auth:sanctum, role:pemilik
     * PARAMETER : $id
     * OUTPUT    : JsonResponse ['success' => bool, 'message' => string]
     */
    public function destroy($id): JsonResponse
    {
        // 1. Cari record data karyawan target berdasarkan parameter ID.
        // 2. Jika data terikat dengan integritas foreign key tabel transaksi, kembalikan response batasan 400.
        // 3. Jalankan fungsi delete data karyawan dari database.
        // 4. Kembalikan response JSON sukses status 200.

        return response()->json([]);
    }
}
