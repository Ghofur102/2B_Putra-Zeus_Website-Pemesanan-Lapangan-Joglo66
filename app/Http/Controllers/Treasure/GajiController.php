<?php

namespace App\Http\Controllers\Treasure;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class GajiController extends Controller
{
    private const MONTH_MAP = [
        1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april',
        5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august',
        9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december'
    ];

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || $user->role !== 'treasurer') {
                throw new HttpException(403, 'Akses ditolak.');
            }

            $validator = Validator::make($request->query(), [
                'bulan' => ['required', 'integer', 'min:1', 'max:12'],
                'tahun' => ['required', 'integer', 'min:2000', 'max:' . date('Y')],
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
            }

            $bulan = (int) $request->bulan;
            $tahun = (int) $request->tahun;
            $monthEnum = self::MONTH_MAP[$bulan];

            $employees = Employee::where('status', 'active')->get();
            $salaries = EmployeeSalary::where('period_month', $monthEnum)
                ->where('period_year', $tahun)
                ->get()
                ->keyBy('fk_employee_id');

            $data = $employees->map(function ($emp) use ($salaries) {
                $salary = $salaries->get($emp->id);
                return [
                    'employee_id' => $emp->id,
                    'name'        => $emp->name,
                    'position'    => $emp->position,
                    'base_salary' => $emp->base_salary,
                    'is_edited'   => $salary ? true : false,
                    'amount_paid' => $salary ? $salary->amount_paid : $emp->base_salary,
                    'bonus'       => $salary ? $salary->bonus : 0,
                    'deduction'   => $salary ? $salary->deduction : 0,
                    'notes'       => $salary ? $salary->notes : '-',
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Data gaji berhasil diambil.',
                'data'    => $data
            ], 200);

        } catch (HttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            if (!$user || $user->role !== 'treasurer') {
                throw new HttpException(403, 'Akses ditolak.');
            }

            $validator = Validator::make($request->all(), [
                'employee_id' => ['required', 'exists:employees,id'],
                'bulan'       => ['required', 'integer', 'min:1', 'max:12'],
                'tahun'       => ['required', 'integer', 'min:2000', 'max:' . date('Y')],
                'amount_paid' => ['required', 'integer', 'min:0'],
                'bonus'       => ['nullable', 'integer', 'min:0'],
                'deduction'   => ['nullable', 'integer', 'min:0'],
                'notes'       => ['nullable', 'string']
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
            }

            $val = $validator->validated();

            $currentYear = (int) date('Y');
            $currentMonth = (int) date('n');
            if ($val['tahun'] > $currentYear || ($val['tahun'] === $currentYear && $val['bulan'] > $currentMonth)) {
                return response()->json(['success' => false, 'message' => 'Tidak dapat menyimpan data untuk bulan di masa depan.'], 403);
            }

            $monthEnum = self::MONTH_MAP[$val['bulan']];
            $totalExpense = $val['amount_paid'] + ($val['bonus'] ?? 0) - ($val['deduction'] ?? 0);

            $field = Field::first();
            $fieldId = $field ? $field->id : 1;

            $salary = EmployeeSalary::where('fk_employee_id', $val['employee_id'])
                ->where('period_month', $monthEnum)
                ->where('period_year', $val['tahun'])
                ->first();

            if ($salary) {
                $expense = Expense::find($salary->fk_expense_id);
                if ($expense) {
                    $expense->update(['amount' => $totalExpense]);
                }
                $salary->update([
                    'amount_paid' => $val['amount_paid'],
                    'bonus'       => $val['bonus'] ?? 0,
                    'deduction'   => $val['deduction'] ?? 0,
                    'notes'       => $val['notes'] ?? '-',
                ]);
            } else {
                $expense = Expense::create([
                    'fk_field_id'  => $fieldId,
                    'fk_user_id'   => $user->id,
                    'category'     => 'Gaji',
                    'amount'       => $totalExpense,
                    'expense_date' => now()->toDateString(),
                    'proof_photo'  => 'system_generated',
                    'generate_at'  => now()->toDateString(),
                ]);

                EmployeeSalary::create([
                    'fk_employee_id' => $val['employee_id'],
                    'fk_expense_id'  => $expense->id,
                    'amount_paid'    => $val['amount_paid'],
                    'period_month'   => $monthEnum,
                    'period_year'    => $val['tahun'],
                    'payment_date'   => now()->toDateString(),
                    'bonus'          => $val['bonus'] ?? 0,
                    'deduction'      => $val['deduction'] ?? 0,
                    'notes'          => $val['notes'] ?? '-',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Gaji berhasil disimpan.'], 200);

        } catch (HttpException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan gaji.', 'error' => $e->getMessage()], 500);
        }
    }

    public function sync(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            if (!$user || $user->role !== 'treasurer') {
                throw new HttpException(403, 'Akses ditolak.');
            }

            $validator = Validator::make($request->all(), [
                'bulan' => ['required', 'integer', 'min:1', 'max:12'],
                'tahun' => ['required', 'integer', 'min:2000', 'max:' . date('Y')],
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
            }

            $val = $validator->validated();

            $currentYear = (int) date('Y');
            $currentMonth = (int) date('n');
            if ($val['tahun'] > $currentYear || ($val['tahun'] === $currentYear && $val['bulan'] > $currentMonth)) {
                return response()->json(['success' => false, 'message' => 'Tidak dapat melakukan sinkronisasi untuk bulan di masa depan.'], 403);
            }

            $monthEnum = self::MONTH_MAP[$val['bulan']];

            $employees = Employee::where('status', 'active')->get();
            $field = Field::first();
            $fieldId = $field ? $field->id : 1;

            $syncedCount = 0;

            foreach ($employees as $emp) {
                $exists = EmployeeSalary::where('fk_employee_id', $emp->id)
                    ->where('period_month', $monthEnum)
                    ->where('period_year', $val['tahun'])
                    ->exists();

                if (!$exists) {
                    $expense = Expense::create([
                        'fk_field_id'  => $fieldId,
                        'fk_user_id'   => $user->id,
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
                        'period_year'    => $val['tahun'],
                        'payment_date'   => now()->toDateString(),
                        'bonus'          => 0,
                        'deduction'      => 0,
                        'notes'          => 'Auto-generated by Sync',
                    ]);

                    $syncedCount++;
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => "$syncedCount data gaji baru berhasil disinkronisasi."], 200);

        } catch (HttpException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal melakukan sinkronisasi.', 'error' => $e->getMessage()], 500);
        }
    }
}