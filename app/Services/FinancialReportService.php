<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\EmployeeSalary;
use App\Models\Payment;
use Carbon\Carbon;

class FinancialReportService
{
    private $formatDate = "Y-m-d H:i:s";
    private const MONTH_MAP = [
        1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april',
        5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august',
        9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december',
    ];

    public function getMonthlyData(int $bulan, int $tahun): array
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
            ->when(!empty($salaryExpenseIds), fn ($query) => $query->whereNotIn('id', $salaryExpenseIds))
            ->get();

        $totalOperasional = $expenses->sum('amount');
        $totalPengeluaran = $totalOperasional + $totalGaji;

        $expenseBreakdown = $expenses->groupBy('category')->map(fn ($group) => [
            'category' => $group->first()->category,
            'amount'   => $group->sum('amount'),
        ])->values();

        $incomeDetails = $payments->filter(fn($p) => in_array(strtolower(trim($p->payment_type)), ['down payment', 'final payment', 'dp hangus']))
            ->map(fn($p) => [
                'id'          => 'inc_' . $p->id,
                'date'        => Carbon::parse($p->paid_at)->format($this->formatDate),
                'type'        => 'income',
                'category'    => $p->payment_type,
                'description' => 'Penyewaan Lapangan (' . ucwords(str_replace('_', ' ', $p->payment_type)) . ')',
                'amount'      => $p->amount,
            ]);

        $expenseDetails = $expenses->map(fn($e) => [
            'id'          => 'exp_' . $e->id,
            'date'        => Carbon::parse($e->expense_date)->format($this->formatDate),
            'type'        => 'expense',
            'category'    => $e->category,
            'description' => 'Pengeluaran Lapangan (' . $e->category . ')',
            'amount'      => $e->amount,
        ]);

        $salaryDetails = $salaries->map(function($s) use ($tahun, $bulan) {
            $dateObj = $s->payment_date ? Carbon::parse($s->payment_date) : Carbon::create($tahun, $bulan, date('t', strtotime("$tahun-$bulan-01")));
            return [
                'id'          => 'sal_' . $s->id,
                'date'        => $dateObj->format($this->formatDate),
                'type'        => 'expense',
                'category'    => 'Gaji',
                'description' => 'Pembayaran Gaji Karyawan',
                'amount'      => $s->amount_paid + $s->bonus - $s->deduction,
            ];
        });

        return [
            'month'              => ucfirst($monthEnum),
            'year'               => $tahun,
            'total_income'       => $totalPemasukan,
            'total_expense'      => $totalPengeluaran,
            'net_profit'         => $totalPemasukan - $totalPengeluaran,
            'generate_at'        => now()->toDateString(),
            'expenses'           => $expenseBreakdown,
            'daily_transactions' => $incomeDetails->concat($expenseDetails)->concat($salaryDetails)->sortByDesc('date')->values()->all(),
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
