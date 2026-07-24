<?php

namespace App\Services\Admin;

use App\Models\Expense;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpenseService
{
    public function getExpenses(array $fieldIds)
    {
        $query = Expense::query();

        if (!empty($fieldIds)) {
            $query->whereIn('fk_field_id', $fieldIds);
        }

        return $query->latest('expense_date')->get()->map(function ($item) {
            /** @var Expense $item */
            return [
                'id'         => $item->id,
                'name'       => $item->name,
                'title'      => $item->name,
                'category'   => $item->category,
                'quantity'   => (int)$item->quantity,
                'unit_price' => (int)$item->unit_price,
                'amount'     => $item->amount,
                'date'       => $item->expense_date,
                'note'       => $item->note ?? '-',
                'proof'      => !empty($item->proof_photo),
                'image'      => $item->proof_photo ? asset('storage/' . $item->proof_photo) : null,
            ];
        });
    }

    public function getUniqueCategories(array $fieldIds): array
    {
        $query = Expense::query();

        if (!empty($fieldIds)) {
            $query->whereIn('fk_field_id', $fieldIds);
        }

        return $query->distinct()->pluck('category')->filter()->values()->toArray();
    }

    public function createExpense(array $data, $user): Expense
    {
        $fieldId = 1;

        if ($user->role === UserRole::WORKER->value) {
            $assignedField = DB::table('field_admins')->where('fk_user_id', $user->id)->first();
            if ($assignedField) {
                $fieldId = $assignedField->fk_field_id;
            }
        }

        $imagePath = null;
        if (isset($data['image'])) {
            $imagePath = $data['image']->store('expenses', 'public');
        }

        return Expense::create([
            'fk_field_id'  => $fieldId,
            'fk_user_id'   => $user->id,
            'name'         => $data['name'],
            'category'     => $data['category'],
            'quantity'     => $data['quantity'],
            'unit_price'   => $data['unit_price'],
            'expense_date' => $data['date'],
            'proof_photo'  => $imagePath,
            'note'         => $data['note'] ?? null,
            'generate_at'  => now(),
        ]);
    }

    public function updateExpense(int $id, array $data): bool
    {
        $expense = Expense::query()->find($id);
        if (!$expense) {
            return false;
        }

        $updateData = [
            'name'         => $data['name'],
            'category'     => $data['category'],
            'quantity'     => $data['quantity'],
            'unit_price'   => $data['unit_price'],
            'expense_date' => $data['date'],
            'note'         => $data['note'] ?? null,
        ];

        if (isset($data['image'])) {
            if ($expense->proof_photo && Storage::disk('public')->exists($expense->proof_photo)) {
                Storage::disk('public')->delete($expense->proof_photo);
            }
            $updateData['proof_photo'] = $data['image']->store('expenses', 'public');
        }

        return (bool) $expense->update($updateData);
    }

    public function deleteExpense(int $id): bool
    {
        $expense = Expense::query()->find($id);
        if (!$expense) {
            return false;
        }

        if ($expense->proof_photo && Storage::disk('public')->exists($expense->proof_photo)) {
            Storage::disk('public')->delete($expense->proof_photo);
        }

        return (bool) $expense->delete();
    }
}
