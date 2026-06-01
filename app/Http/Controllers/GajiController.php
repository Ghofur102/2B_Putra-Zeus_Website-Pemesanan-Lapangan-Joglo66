<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GajiController extends Controller
{
    /**
     * DEVELOPER : Zami
     * ROUTE     : POST /api/gaji
     * MIDDLEWARE: auth:sanctum, role:bendahara|pemilik
     * PARAMETER : Request $request [fk_user_id, bulan, tahun, nominal_gaji]
     * OUTPUT    : JsonResponse ['success' => bool, 'message' => string, 'data' => object]
     */
    public function store(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'fk_user_id'   => 'required|integer|exists:employees,id',
            'bulan'        => 'required|integer|between:1,12',
            'tahun'        => 'required|integer|min:2020|max:2100',
            'nominal_gaji' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $exists = DB::table('pengeluaran_gaji')
            ->where('fk_user_id', $request->fk_user_id)
            ->where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Data gaji karyawan pada periode tersebut sudah ada',
            ], 409);
        }

        $id = DB::table('pengeluaran_gaji')->insertGetId([
            'fk_user_id'   => $request->fk_user_id,
            'bulan'        => $request->bulan,
            'tahun'        => $request->tahun,
            'nominal_gaji' => $request->nominal_gaji,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $data = DB::table('pengeluaran_gaji')->find($id);

        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => 'Gaji berhasil dicatat',
        ], 201);
    }
}
