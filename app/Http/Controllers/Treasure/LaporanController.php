<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\EmployeeSalary;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class LaporanController extends Controller
{
    /**
     * DEVELOPER : Huda
     * ROUTE     : GET /api/admin/laporan-bulanan
     * MIDDLEWARE: auth:sanctum, check.field.admin
     * PARAMETER : Request $request (query: 'bulan', 'tahun')
     * OUTPUT    : JsonResponse ['success' => bool, 'message' => string, 'data' => array]
     */

    // Map angka bulan ke nama enum di tabel employee_salaries
    private const MONTH_MAP = [
        1  => 'january',   2  => 'february', 3  => 'march',
        4  => 'april',     5  => 'may',       6  => 'june',
        7  => 'july',      8  => 'august',    9  => 'september',
        10 => 'october',   11 => 'november',  12 => 'december',
    ];

    public function index(Request $request): JsonResponse
    {
        $status = 200;

        try {
            // Cek role: hanya bendahara yang boleh akses
            $user = $request->user();
            if (!$user || $user->role !== 'bendahara') {
                throw new HttpException(403, 'Forbidden. Hanya bendahara yang dapat mengakses laporan bulanan.');
            }

            // 1. Validasi input query parameter 'bulan' (1-12) dan 'tahun' (integer).
            $validator = Validator::make($request->query(), [
                'bulan' => ['required', 'integer', 'min:1', 'max:12'],
                'tahun' => ['required', 'integer', 'min:2000', 'max:' . date('Y')],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $bulan      = (int) $validator->validated()['bulan'];
            $tahun      = (int) $validator->validated()['tahun'];
            $monthEnum  = self::MONTH_MAP[$bulan];

            // 2. Ambil data agregat transaksi bulanan dari payments
            //    (Pemasukan Booking: down payment + final payment)
            $payments = Payment::whereYear('paid_at', $tahun)
                ->whereMonth('paid_at', $bulan)
                ->where('status', 'success')
                ->get();

            $totalDP        = $payments->where('payment_type', 'down payment')->sum('amount');
            $totalPelunasan = $payments->where('payment_type', 'final payment')->sum('amount');
            $totalAtribut   = $payments->where('payment_type', 'attribute rental')->sum('amount');
            $totalPemasukan = $totalDP + $totalPelunasan + $totalAtribut;

            // 3. Tarik total nominal DP hangus pada periode bulan berjalan.
            $totalDPHangus = $payments->where('payment_type', 'dp hangus')->sum('amount');

            // 4. Ambil pengeluaran operasional dari tabel expenses (non-gaji).
            $expenses = Expense::whereYear('expense_date', $tahun)
                ->whereMonth('expense_date', $bulan)
                ->get();

            $totalOperasional = $expenses->sum('amount');

            $expenseBreakdown = $expenses->groupBy('category')->map(fn ($group) => [
                'category' => $group->first()->category,
                'amount'   => $group->sum('amount'),
            ])->values();

            // Ambil pengeluaran gaji karyawan dari tabel employee_salaries.
            $salaries = EmployeeSalary::where('period_month', $monthEnum)
                ->where('period_year', $tahun)
                ->get();

            $totalGaji = $salaries->sum(fn ($s) =>
                $s->amount_paid + $s->bonus - $s->deduction
            );

            // 5. Kalkulasi laba/rugi bersih:
            //    (Total Pemasukan + DP Hangus) - Pengeluaran Operasional - Total Gaji
            $netProfit = ($totalPemasukan + $totalDPHangus) - $totalOperasional - $totalGaji;

            // 6. Kembalikan response JSON 200 berupa data summary neraca keuangan bulanan.
            $data = [
                'success' => true,
                'message' => 'Data laporan bulanan berhasil diambil.',
                'data'    => [
                    'periode'      => [
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'label' => ucfirst($monthEnum) . ' ' . $tahun,
                    ],
                    'pemasukan'    => [
                        'total_dp'        => $totalDP,
                        'total_pelunasan' => $totalPelunasan,
                        'total_atribut'   => $totalAtribut,
                        'total_dp_hangus' => $totalDPHangus,
                        'total'           => $totalPemasukan,
                    ],
                    'pengeluaran'  => [
                        'operasional'     => $totalOperasional,
                        'gaji'            => $totalGaji,
                        'total'           => $totalOperasional + $totalGaji,
                        'breakdown'       => $expenseBreakdown,
                    ],
                    'net_profit'   => $netProfit,
                    'generate_at'  => now()->toDateString(),
                ],
            ];

        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data   = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data   = [
                'success' => false,
                'message' => 'Gagal mengambil data laporan bulanan.',
                'error'   => $e->getMessage(),
            ];
        }

        return response()->json($data, $status);
    }
}