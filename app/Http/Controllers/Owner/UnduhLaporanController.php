<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class UnduhLaporanController extends Controller
{
    /**
     * DEVELOPER : Zami
     * ROUTE     : GET /api/owner/laporan-pdf/preview
     * MIDDLEWARE: auth:sanctum, role:pemilik
     * PARAMETER : Request $request (query: 'bulan', 'tahun')
     * OUTPUT    : JsonResponse ['success' => bool, 'data' => array]
     */
    public function preview(Request $request): JsonResponse
    {
        // 1. Validasi filter bulan dan tahun yang dikirim aktor Pemilik.
        // 2. Hubungi LaporanController / query database untuk mendapatkan array ringkasan neraca laba rugi bulanan.
        // 3. Jika target data periode kosong, return response error 404 (E1).
        // 4. Kirimkan response data mentah terstruktur untuk digambar sebagai preview halaman di aplikasi frontend.

        return response()->json([]);
    }

    /**
     * DEVELOPER : Zami
     * ROUTE     : GET /api/owner/laporan-pdf/download
     * MIDDLEWARE: auth:sanctum, role:pemilik
     * PARAMETER : Request $request (query: 'bulan', 'tahun')
     * OUTPUT    : Response (Binary PDF File Stream)
     */
    public function download(Request $request)
    {
        // 1. Ambil data laporan keuangan bulanan terperinci berdasarkan filter bulan & tahun terpilih.
        // 2. Compile data ke dalam view blade HTML khusus template cetak laporan.
        // 3. Gunakan library (seperti DomPDF / Barryvdh-Dompdf) untuk melakukan render instruksi generate stream file PDF (E2 jika gagal).
        // 4. Kembalikan response stream binary file download PDF dengan header tipe 'application/pdf'.
    }
}
