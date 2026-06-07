<?php

namespace App\Http\Controllers\Treasure;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class LaporanController extends Controller
{
    public function index(Request $request, FinancialReportService $reportService): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || $user->role !== 'treasurer') {
                throw new HttpException(403, 'Forbidden. Hanya bendahara yang dapat mengakses laporan bulanan.');
            }

            $validator = Validator::make($request->query(), [
                'bulan' => ['required', 'integer', 'min:1', 'max:12'],
                'tahun' => ['required', 'integer', 'min:2000', 'max:' . date('Y')],
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
            }

            $data = $reportService->getMonthlyData((int) $request->bulan, (int) $request->tahun);

            return response()->json([
                'success' => true,
                'message' => 'Data laporan bulanan berhasil diambil.',
                'data'    => $data,
            ], 200);

        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $response = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $response = ['success' => false, 'message' => 'Gagal mengambil data laporan bulanan.', 'error' => $e->getMessage()];
        }

        return response()->json($response, $status);
        
    }
}
