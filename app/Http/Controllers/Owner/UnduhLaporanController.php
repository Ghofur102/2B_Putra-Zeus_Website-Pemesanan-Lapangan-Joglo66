<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Field;
use App\Models\Payment;
use Carbon\Carbon;
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
        $validator = validator($request->all(), [
            'bulan'    => 'required|integer|between:1,12',
            'tahun'    => 'required|integer|min:2020|max:2100',
            'field_id' => 'nullable|integer|exists:fields,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $this->buildReportData((int) $request->bulan, (int) $request->tahun, $request->field_id);

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Data laporan tidak ditemukan untuk periode tersebut',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
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
        $validator = validator($request->all(), [
            'bulan'    => 'required|integer|between:1,12',
            'tahun'    => 'required|integer|min:2020|max:2100',
            'field_id' => 'nullable|integer|exists:fields,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $this->buildReportData((int) $request->bulan, (int) $request->tahun, $request->field_id);

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Data laporan tidak ditemukan untuk periode tersebut',
            ], 404);
        }

        $field = $request->field_id ? Field::find($request->field_id) : null;

        $html = view('pdf.laporan-bulanan', [
            'monthName'    => $this->monthName((int) $request->bulan),
            'year'         => $request->tahun,
            'field'        => $field,
            'total_income' => $data['total_income'],
            'total_expense'=> $data['total_expense'],
            'net_profit'   => $data['net_profit'],
            'income'       => $data['details']['income'],
            'expense'      => $data['details']['expense'],
            'generateAt'   => Carbon::now()->format('d/m/Y H:i'),
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html);

        return new Response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-bulanan-' . $request->bulan . '-' . $request->tahun . '.pdf"',
        ]);
    }

    private function buildReportData(int $bulan, int $tahun, ?int $fieldId): ?array
    {
        $field = $fieldId ? Field::find($fieldId) : null;

        $bookingQuery = BookingDetail::whereYear('play_date', $tahun)
            ->whereMonth('play_date', $bulan)
            ->whereNotIn('status', ['cancelled']);

        if ($fieldId) {
            $bookingQuery->whereHas('booking', fn($q) => $q->where('fk_field_id', $fieldId));
        }

        $totalBooking = (int) $bookingQuery->sum('price');

        $dpQuery = Payment::where('payment_type', 'down payment')
            ->where('status', 'success')
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', $tahun)
            ->whereMonth('paid_at', $bulan);

        $fpQuery = Payment::where('payment_type', 'final payment')
            ->where('status', 'success')
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', $tahun)
            ->whereMonth('paid_at', $bulan);

        if ($fieldId) {
            $dpQuery->whereHas('booking', fn($q) => $q->where('fk_field_id', $fieldId));
            $fpQuery->whereHas('booking', fn($q) => $q->where('fk_field_id', $fieldId));
        }

        $totalDp = (int) $dpQuery->sum('amount');
        $totalFp = (int) $fpQuery->sum('amount');

        $forsakenQuery = Payment::where('payment_type', 'down payment')
            ->where('status', 'success')
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', $tahun)
            ->whereMonth('paid_at', $bulan)
            ->whereHas('bookingDetail', fn($q) => $q->where('status', 'cancelled'))
            ->whereDoesntHave('bookingDetail.payment', fn($q) => $q->where('payment_type', 'refund')->where('status', 'success'));

        if ($fieldId) {
            $forsakenQuery->whereHas('booking', fn($q) => $q->where('fk_field_id', $fieldId));
        }

        $totalForsaken = (int) $forsakenQuery->sum('amount');

        $expenseQuery = Expense::whereYear('expense_date', $tahun)
            ->whereMonth('expense_date', $bulan);

        if ($fieldId) {
            $expenseQuery->where('fk_field_id', $fieldId);
        }

        $totalOperational = (int) $expenseQuery->sum('amount');

        $totalSalary = (int) EmployeeSalary::where('period_year', $tahun)
            ->where('period_month', $this->monthName($bulan))
            ->sum('amount_paid');

        $totalIncome = $totalBooking + $totalDp + $totalFp + $totalForsaken;
        $totalExpense = $totalOperational + $totalSalary;
        $netProfit = $totalIncome - $totalExpense;

        $reportId = null;
        $reportRecord = \App\Models\FinancialReport::whereYear('generate_at', $tahun)
            ->whereMonth('generate_at', $bulan)
            ->when($fieldId, fn($q) => $q->where('fk_field_id', $fieldId))
            ->first();

        if ($reportRecord) {
            $reportId = $reportRecord->id;
        }

        return [
            'id'            => $reportId ?? 0,
            'month'         => $this->monthName($bulan),
            'year'          => $tahun,
            'total_income'  => $totalIncome,
            'total_expense' => $totalExpense,
            'net_profit'    => max($netProfit, 0),
            'field'         => $field ? ['id' => $field->id, 'name' => $field->name] : null,
            'details'       => [
                'income'  => [
                    'booking'             => $totalBooking,
                    'down_payment'        => $totalDp,
                    'final_payment'       => $totalFp,
                    'forsaken_downpayment'=> $totalForsaken,
                ],
                'expense' => [
                    'operational' => $totalOperational,
                    'salary'      => $totalSalary,
                ],
            ],
            'generate_at'   => Carbon::now()->format('Y-m-d'),
        ];
    }

    private function monthName(int $bulan): string
    {
        return [
            1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april',
            5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august',
            9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december',
        ][$bulan] ?? 'january';
    }
}
