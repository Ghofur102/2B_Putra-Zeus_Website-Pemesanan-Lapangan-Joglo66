<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExpenseController extends Controller
{
    use \App\Http\Controllers\Traits\FieldAccessTrait;

    public function listExpense(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Expense::query();

            if ($user && $user->role === 'worker') {
                $fieldIds = DB::table('field_admins')->where('fk_user_id', $user->id)->pluck('fk_field_id');
                $query->whereIn('fk_field_id', $fieldIds);
            }

            $expenses = $query->latest('expense_date')->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->category,
                    'category' => $item->category,
                    'amount' => (int)$item->amount,
                    'date' => $item->expense_date,
                    'note' => $item->note ?? '-',
                    'proof' => !empty($item->proof_photo),
                    'image' => $item->proof_photo ? asset('storage/' . $item->proof_photo) : null,
                ];
        });

            return response()->json(['success' => true, 'data' => $expenses]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memuat data pengeluaran.'], 500);
        }
    }

    public function getCategories(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Expense::query();

            if ($user && $user->role === 'worker') {
                $fieldIds = DB::table('field_admins')->where('fk_user_id', $user->id)->pluck('fk_field_id');
                $query->whereIn('fk_field_id', $fieldIds);
            }

            $categories = $query->distinct()->pluck('category')->filter()->values()->toArray();

            return response()->json(['success' => true, 'data' => $categories]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memuat daftar kategori.'], 500);
        }
    }

    public function addExpense(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'nominal' => 'required|integer|min:0',
            'date' => 'required|date',
            'note' => 'nullable|string',
            'image' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        try {
            $user = $request->user();
            $fieldId = 1;

            if ($user && $user->role === 'worker') {
                $assignedField = DB::table('field_admins')->where('fk_user_id', $user->id)->first();
                if ($assignedField) {
                    $fieldId = $assignedField->fk_field_id;
                }
            }

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('expenses', 'public');
            }

            $expense = Expense::create([
                'fk_field_id' => $fieldId,
                'fk_user_id' => $user->id,
                'category' => $request->category,
                'amount' => $request->nominal,
                'expense_date' => $request->date,
                'proof_photo' => $imagePath,
                'note' => $request->note,
                'generate_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Data pengeluaran berhasil disimpan.', 'data' => $expense], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan data ke database.'], 500);
        }
    }

    public function detailExpense(Request $request, $id): JsonResponse
    {
        try {
            $expense = Expense::find($id);
            if (!$expense) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            $expense->delete();
            return response()->json(['success' => true, 'message' => 'Data pengeluaran berhasil dihapus.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data.'], 500);
        }
    }
}
