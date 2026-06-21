<?php

namespace App\Services\Owner;

use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeSalary;
use App\Enums\GeneralStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EmployeeService
{
    public function getEmployeesList()
    {
        return Employee::query()->with('user')->get()->map(function ($emp) {
            /** @var Employee $emp */ // Type Hinting untuk mengamankan autocomplete IDE Anda
            return [
                'id'           => $emp->id,
                'user_id'      => $emp->fk_user_id,
                'name'         => $emp->name,
                'email'        => $emp->user->email ?? null,
                'role'         => $emp->user->role ?? null,
                'phone_number' => $emp->phone_number,
                'address'      => $emp->address,
                'position'     => $emp->position,
                'base_salary'  => $emp->base_salary,
                'join_date'    => $emp->join_date,
                'status'       => $emp->status,
                'is_system'    => $emp->fk_user_id !== null,
            ];
        });
    }

    public function storeEmployee(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $userId = null;
            $isSystem = filter_var($data['is_system'], FILTER_VALIDATE_BOOLEAN);

            if ($isSystem) {
                $user = User::create([
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'password' => Hash::make($data['password']),
                    'role'     => $data['role'],
                ]);
                $userId = $user->id;
            }

            return Employee::create([
                'fk_user_id'   => $userId,
                'name'         => $data['name'],
                'phone_number' => $data['phone_number'] ?? null,
                'address'      => $data['address'] ?? null,
                'position'     => $data['position'],
                'base_salary'  => $data['base_salary'],
                'join_date'    => $data['join_date'],
                'status'       => GeneralStatus::ACTIVE->value,
            ]);
        });
    }

    public function updateEmployee(Employee $employee, array $data): void
    {
        DB::transaction(function () use ($employee, $data) {
            $isSystem = filter_var($data['is_system'], FILTER_VALIDATE_BOOLEAN);
            $userId = $employee->fk_user_id;

            if ($isSystem && !$userId) {
                $user = User::create([
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'password' => Hash::make($data['password']),
                    'role'     => $data['role'],
                ]);
                $userId = $user->id;
            } elseif ($isSystem && $userId) {
                $user = User::findOrFail($userId);
                $userData = ['name' => $data['name'], 'email' => $data['email'], 'role' => $data['role']];

                if (!empty($data['password'])) {
                    $userData['password'] = Hash::make($data['password']);
                }
                $user->update($userData);
            } elseif (!$isSystem && $userId) {
                User::where('id', $userId)->delete();
                $userId = null;
            }

            $employee->update([
                'fk_user_id'   => $userId,
                'name'         => $data['name'],
                'phone_number' => $data['phone_number'] ?? null,
                'address'      => $data['address'] ?? null,
                'position'     => $data['position'],
                'base_salary'  => $data['base_salary'],
                'status'       => $data['status'],
            ]);
        });
    }

    public function destroyEmployee(Employee $employee): void
    {
        $hasSalaryRecords = EmployeeSalary::where('fk_employee_id', $employee->id)->exists();
        if ($hasSalaryRecords) {
            throw new ConflictHttpException('Karyawan tidak dapat dihapus karena memiliki riwayat penggajian.');
        }

        DB::transaction(function () use ($employee) {
            $userId = $employee->fk_user_id;
            $employee->delete();

            if ($userId) {
                User::where('id', $userId)->delete();
            }
        });
    }
}
