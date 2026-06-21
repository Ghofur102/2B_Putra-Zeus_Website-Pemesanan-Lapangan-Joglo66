<?php

namespace App\Services\Treasure;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Field;
use App\Enums\GeneralStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SalaryService
{
    private const MONTH_MAP = [
        1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april',
        5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august',
        9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december'
    ];

    public function getSalaryList(int $month, int $year): Collection
    {
        $monthEnum = self::MONTH_MAP[$month];

        $employees = Employee::query()->where('status', GeneralStatus::ACTIVE->value)->get();
        $salaries = EmployeeSalary::query()
            ->where('period_month', $monthEnum)
            ->where('period_year', $year)
            ->get()
            ->keyBy('fk_employee_id');

        return $employees->map(function ($emp) use ($salaries) {
            /** @var Employee $emp */
            $salary = $salaries->get($emp->id);
            return [
                'employee_id' => $emp->id,
                'name'        => $emp->name,
                'position'    => $emp->position,
                'base_salary' => $emp->base_salary,
                'is_edited'   => $salary !== null,
                'amount_paid' => $salary ? $salary->amount_paid : $emp->base_salary,
                'bonus'       => $salary ? $salary->bonus : 0,
                'deduction'   => $salary ? $salary->deduction : 0,
                'notes'       => $salary ? $salary->notes : '-',
            ];
        });
    }

    public function updateOrCalculateSalary(array $data, int $treasurerUserId): void
    {
        $this->validateNotFutureDate($data['bulan'], $data['tahun']);

        $monthEnum = self::MONTH_MAP[$data['bulan']];
        $totalExpense = $data['amount_paid'] + ($data['bonus'] ?? 0) - ($data['deduction'] ?? 0);

        $field = Field::query()->first();
        $fieldId = $field ? $field->id : 1;

        DB::transaction(function () use ($data, $monthEnum, $totalExpense, $fieldId, $treasurerUserId) {
            $salary = EmployeeSalary::query()
                ->where('fk_employee_id', $data['employee_id'])
                ->where('period_month', $monthEnum)
                ->where('period_year', $data['tahun'])
                ->first();

            if ($salary) {
                $expense = Expense::query()->find($salary->fk_expense_id);
                if ($expense) {
                    $expense->update(['amount' => $totalExpense]);
                }
                $salary->update([
                    'amount_paid' => $data['amount_paid'],
                    'bonus'       => $data['bonus'] ?? 0,
                    'deduction'   => $data['deduction'] ?? 0,
                    'notes'       => $data['notes'] ?? '-',
                ]);
            } else {
                $expense = Expense::create([
                    'fk_field_id'  => $fieldId,
                    'fk_user_id'   => $treasurerUserId,
                    'category'     => 'Gaji',
                    'amount'       => $totalExpense,
                    'expense_date' => now()->toDateString(),
                    'proof_photo'  => 'system_generated',
                    'generate_at'  => now()->toDateString(),
                ]);

                EmployeeSalary::create([
                    'fk_employee_id' => $data['employee_id'],
                    'fk_expense_id'  => $expense->id,
                    'amount_paid'    => $data['amount_paid'],
                    'period_month'   => $monthEnum,
                    'period_year'    => $data['tahun'],
                    'payment_date'   => now()->toDateString(),
                    'bonus'          => $data['bonus'] ?? 0,
                    'deduction'      => $data['deduction'] ?? 0,
                    'notes'          => $data['notes'] ?? '-',
                ]);
            }
        });
    }

    public function syncMonthlySalaries(array $data, int $treasurerUserId): int
    {
        $this->validateNotFutureDate($data['bulan'], $data['tahun']);

        $monthEnum = self::MONTH_MAP[$data['bulan']];
        $employees = Employee::query()->where('status', GeneralStatus::ACTIVE->value)->get();

        $field = Field::query()->first();
        $fieldId = $field ? $field->id : 1;

        return DB::transaction(function () use ($employees, $monthEnum, $data, $fieldId, $treasurerUserId) {
            $syncedCount = 0;

            foreach ($employees as $emp) {
                /** @var Employee $emp */
                $exists = EmployeeSalary::query()
                    ->where('fk_employee_id', $emp->id)
                    ->where('period_month', $monthEnum)
                    ->where('period_year', $data['tahun'])
                    ->exists();

                if (!$exists) {
                    $expense = Expense::create([
                        'fk_field_id'  => $fieldId,
                        'fk_user_id'   => $treasurerUserId,
                        'category'     => 'Gaji',
                        'amount'       => $emp->base_salary,
                        'expense_date' => now()->toDateString(),
                        'proof_photo'  => 'system_generated',
                        'generate_at'  => now()->toDateString(),
                    ]);

                    EmployeeSalary::create([
                        'fk_employee_id' => $emp->id,
                        'fk_expense_id'  => $expense->id,
                        'amount_paid'    => $emp->base_salary,
                        'period_month'   => $monthEnum,
                        'period_year'    => $data['tahun'],
                        'payment_date'   => now()->toDateString(),
                        'bonus'          => 0,
                        'deduction'      => 0,
                        'notes'          => 'Auto-generated by Sync',
                    ]);

                    $syncedCount++;
                }
            }

            return $syncedCount;
        });
    }

    private function validateNotFutureDate(int $month, int $year): void
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        if ($year > $currentYear || ($year === $currentYear && $month > $currentMonth)) {
            throw new HttpException(403, 'Tidak dapat memproses data untuk bulan di masa depan.');
        }
    }
}
