<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetMonthlyReportRequest;
use App\Services\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class LaporanController extends Controller
{
    protected FinancialReportService $reportService;

    public function __construct(FinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(GetMonthlyReportRequest $request): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $user = $request->user();
            if (!$user) {
                throw new HttpException(403, 'Forbidden. Hanya pengguna login yang dapat mengakses laporan bulanan.');
            }

            $validated = $request->validated();

            $reportData = $this->reportService->getMonthlyData((int) $validated['bulan'], (int) $validated['tahun']);

            $data = [
                'success' => true,
                'message' => 'Data laporan bulanan berhasil diambil.',
                'data'    => $reportData,
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Gagal mengambil data laporan bulanan.',
                'error'   => $e->getMessage()
            ];
        }

        return response()->json($data, $status);
    }
}
