<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Field;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;

class UnduhLaporanController extends Controller
{
    private const MONTH_MAP = [
        1  => 'january',   2  => 'february', 3  => 'march',
        4  => 'april',     5  => 'may',      6  => 'june',
        7  => 'july',      8  => 'august',   9  => 'september',
        10 => 'october',   11 => 'november',  12 => 'december',
    ];

    public function preview(Request $request): JsonResponse
    {
        $data = $this->validatedReportData($request);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        $data['download_url'] = URL::temporarySignedRoute(
            'owner.laporan.download',
            now()->addMinutes(30),
            [
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
                'field_id' => $request->field_id
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Preview laporan berhasil diambil.',
            'data'    => $data,
        ]);
    }

    public function download(Request $request)
    {
        $data = $this->validatedReportData($request);

        if ($data instanceof JsonResponse) {
            return $data;
        }

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

        return new Response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-bulanan-' . $request->bulan . '-' . $request->tahun . '.pdf"',
        ]);
    }

    private function validatedReportData(Request $request): array|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . date('Y')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        return $this->buildReportData($bulan, $tahun);
    }

    private function buildReportData(int $bulan, int $tahun): array
    {
        $monthEnum = self::MONTH_MAP[$bulan];
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate   = Carbon::create($tahun, $bulan, 1)->endOfMonth();

        $payments = Payment::whereBetween('paid_at', [$startDate, $endDate])
            ->where('status', 'success')
            ->get();

        $totalDP        = $payments->filter(fn($p) => strtolower(trim($p->payment_type)) === 'down payment')->sum('amount');
        $totalPelunasan = $payments->filter(fn($p) => strtolower(trim($p->payment_type)) === 'final payment')->sum('amount');
        $totalDPHangus  = $payments->filter(fn($p) => strtolower(trim($p->payment_type)) === 'dp hangus')->sum('amount');
        $totalAtribut   = $payments->filter(fn($p) => in_array(strtolower(trim($p->payment_type)), ['attribute rental', 'attribute']))->sum('amount');

        $totalBooking   = $totalDP + $totalPelunasan;
        $totalPemasukan = $totalBooking + $totalAtribut + $totalDPHangus;

        $salaries = EmployeeSalary::where('period_month', $monthEnum)
            ->where('period_year', $tahun)
            ->get();

        $totalGaji = $salaries->sum(fn ($s) => $s->amount_paid + $s->bonus - $s->deduction);

        $salaryExpenseIds = $salaries->pluck('fk_expense_id')->filter()->toArray();

        $expenses = Expense::whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when(!empty($salaryExpenseIds), function ($query) use ($salaryExpenseIds) {
                return $query->whereNotIn('id', $salaryExpenseIds);
            })
            ->get();

        $totalOperasional = $expenses->sum('amount');
        $totalPengeluaran = $totalOperasional + $totalGaji;

        $expenseBreakdown = $expenses->groupBy('category')->map(fn ($group) => [
            'category' => $group->first()->category,
            'amount'   => $group->sum('amount'),
        ])->values();

        $netProfit = $totalPemasukan - $totalPengeluaran;

        $incomeDetails = $payments->filter(function($p) {
            return in_array(strtolower(trim($p->payment_type)), ['down payment', 'final payment', 'dp hangus']);
        })->map(function($p) {
            return [
                'id'          => 'inc_' . $p->id,
                'date'        => Carbon::parse($p->paid_at)->format('Y-m-d H:i:s'),
                'type'        => 'income',
                'category'    => $p->payment_type,
                'description' => 'Penyewaan Lapangan (' . ucwords(str_replace('_', ' ', $p->payment_type)) . ')',
                'amount'      => $p->amount,
            ];
        });

        $expenseDetails = $expenses->map(function($e) {
            return [
                'id'          => 'exp_' . $e->id,
                'date'        => Carbon::parse($e->expense_date)->format('Y-m-d H:i:s'),
                'type'        => 'expense',
                'category'    => $e->category,
                'description' => 'Pengeluaran Lapangan (' . $e->category . ')',
                'amount'      => $e->amount,
            ];
        });

        $salaryDetails = $salaries->map(function($s) use ($tahun, $bulan) {
            $dateObj = $s->payment_date ? Carbon::parse($s->payment_date) : Carbon::create($tahun, $bulan, date('t', strtotime("$tahun-$bulan-01")));
            return [
                'id'          => 'sal_' . $s->id,
                'date'        => $dateObj->format('Y-m-d H:i:s'),
                'type'        => 'expense',
                'category'    => 'Gaji',
                'description' => 'Pembayaran Gaji Karyawan',
                'amount'      => $s->amount_paid + $s->bonus - $s->deduction,
            ];
        });

        $dailyTransactions = $incomeDetails->concat($expenseDetails)->concat($salaryDetails)->sortByDesc('date')->values()->all();

        return [
            'month'              => ucfirst($monthEnum),
            'year'               => $tahun,
            'total_income'       => $totalPemasukan,
            'total_expense'      => $totalPengeluaran,
            'net_profit'         => $netProfit,
            'generate_at'        => now()->toDateString(),
            'expenses'           => $expenseBreakdown,
            'daily_transactions' => $dailyTransactions,
            'details'            => [
                'income' => [
                    'booking'              => $totalBooking,
                    'down_payment'         => $totalDP,
                    'final_payment'        => $totalPelunasan,
                    'forsaken_downpayment' => $totalDPHangus,
                    'attribute_rental'     => $totalAtribut,
                ],
                'expense' => [
                    'operational' => $totalOperasional,
                    'salary'      => $totalGaji,
                ],
            ],
        ];
    }
}
