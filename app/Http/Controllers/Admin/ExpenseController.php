<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Services\Admin\ExpenseService;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExpenseController extends Controller
{
    use \App\Http\Controllers\Traits\FieldAccessTrait;

    protected ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    public function listExpense(Request $request): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $user = $request->user();
            $fieldIds = [];

            if ($user && $user->role === UserRole::WORKER->value) {
                $fieldIds = DB::table('field_admins')->where('fk_user_id', $user->id)->pluck('fk_field_id')->toArray();
            }

            $expenses = $this->expenseService->getExpenses($fieldIds);
            $data = ['success' => true, 'data' => $expenses];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal memuat data pengeluaran.'];
        }

        return response()->json($data, $status);
    }

    public function getCategories(Request $request): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $user = $request->user();
            $fieldIds = [];

            if ($user && $user->role === UserRole::WORKER->value) {
                $fieldIds = DB::table('field_admins')->where('fk_user_id', $user->id)->pluck('fk_field_id')->toArray();
            }

            $categories = $this->expenseService->getUniqueCategories($fieldIds);
            $data = ['success' => true, 'data' => $categories];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal memuat daftar kategori.'];
        }

        return response()->json($data, $status);
    }

    public function addExpense(StoreExpenseRequest $request): JsonResponse
    {
        $status = 201;
        $data = [];

        try {
            $expense = $this->expenseService->createExpense($request->validated(), $request->user());

            $data = [
                'success' => true,
                'message' => 'Data pengeluaran berhasil disimpan.',
                'data'    => $expense
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal menyimpan data ke database.'];
        }

        return response()->json($data, $status);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $deleted = $this->expenseService->deleteExpense((int)$id);

            if (!$deleted) {
                $status = 404;
                $data = ['success' => false, 'message' => 'Data tidak ditemukan.'];
            } else {
                $data = ['success' => true, 'message' => 'Data pengeluaran berhasil dihapus.'];
            }
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal menghapus data.'];
        }

        return response()->json($data, $status);
    }
}
