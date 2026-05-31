<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GajiController extends Controller
{
    /**
     * DEVELOPER : Zami
     * ROUTE     : POST /api/admin/gaji
     * MIDDLEWARE: auth:sanctum, role:bendahara|pemilik
     * PARAMETER : Request $request [fk_user_id, bulan, tahun, nominal_gaji]
     * OUTPUT    : JsonResponse ['success' => bool, 'message' => string, 'data' => object]
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validasi ketat input field (fk_user_id, bulan, tahun, nominal_gaji). Return error 422 jika kosong (E1).
        // 2. Lakukan pengecekan ke tabel pengeluaran_gaji untuk memastikan data gaji karyawan pada periode bulan & tahun tersebut belum pernah diinput (E2).
        // 3. Jika lolos validasi, simpan data pencatatan transaksi pengeluaran gaji ke database.
        // 4. Kembalikan response sukses status 201 beserta payload log pengeluaran yang tersimpan.

        return response()->json([]);
    }
}
