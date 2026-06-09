<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class UnduhLaporanController extends Controller
{
    public function download(Request $request, FinancialReportService $reportService)
    {
        $status = 200;
        $headers = [];
        $tokenString = $request->query('t');
        $token = $tokenString ? PersonalAccessToken::findToken($tokenString) : null;

        if (!$tokenString) {
            $status = 401;
            $response = 'Akses Ditolak: Token keamanan tidak ditemukan.';
        } elseif (!$token || !$token->tokenable || $token->tokenable->role !== 'owner') {
            $status = 403;
            $response = 'Akses Ditolak: Sesi Anda tidak valid atau Anda bukan Owner.';
        } else {
            $data = $this->validatedReportData($request, $reportService);

            if ($data instanceof JsonResponse) {
                $status = 404;
                $response = 'Data laporan gagal divalidasi.';
            } else {
                $html = view('pdf.laporan-bulanan', [
                'monthName'     => $data['month'],
                'year'          => $data['year'],
                'total_income'  => $data['total_income'],
                'total_expense' => $data['total_expense'],
                'net_profit'    => $data['net_profit'],
                'income'        => $data['details']['income'],
                'expense'       => $data['details']['expense'],
                'generateAt'    => Carbon::now()->format('d/m/Y H:i'),
                ])->render();

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html);
                $response = $pdf->output();

                $headers = [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="laporan-bulanan-' . $request->bulan . '-' . $request->tahun . '.pdf"',
                ];
            }
        }
    return $status === 200
        ? new Response($response, $status, $headers)
        : response($response, $status);
    }

    private function validatedReportData(Request $request, FinancialReportService $reportService): array|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . date('Y')],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        return $reportService->getMonthlyData((int) $request->bulan, (int) $request->tahun);
    }
}
