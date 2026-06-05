<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class KaryawanController extends Controller
{
    public function index(): JsonResponse
    {
        $employees = Employee::with('user')->get()->map(function ($emp) {
            return [
                'id'           => $emp->id,
                'user_id'      => $emp->fk_user_id,
                'name'         => $emp->name,
                'email'        => $emp->user ? $emp->user->email : null,
                'role'         => $emp->user ? $emp->user->role : 'worker',
                'phone_number' => $emp->phone_number,
                'address'      => $emp->address,
                'position'     => $emp->position,
                'base_salary'  => $emp->base_salary,
                'join_date'    => $emp->join_date,
                'status'       => $emp->status,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $employees
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'name'         => 'required|string|max:60',
                'email'        => 'required|email|unique:users,email',
                'password'     => 'required|string|min:8',
                'role'         => 'required|in:worker,manager',
                'phone_number' => 'nullable|string|max:20',
                'address'      => 'nullable|string',
                'position'     => 'required|string|max:50',
                'base_salary'  => 'required|integer|min:0',
                'join_date'    => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $val = $validator->validated();

            $user = User::create([
                'name'     => $val['name'],
                'email'    => $val['email'],
                'password' => Hash::make($val['password']),
                'role'     => $val['role'],
            ]);

            $employee = Employee::create([
                'fk_user_id'   => $user->id,
                'name'         => $val['name'],
                'phone_number' => $val['phone_number'] ?? null,
                'address'      => $val['address'] ?? null,
                'position'     => $val['position'],
                'base_salary'  => $val['base_salary'],
                'join_date'    => $val['join_date'],
                'status'       => 'active',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil ditambahkan.',
                'data'    => $employee
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan karyawan.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $employee = Employee::find($id);

            if (!$employee) {
                throw new HttpException(404, 'Data karyawan tidak ditemukan.');
            }

            $userId = $employee->fk_user_id;

            $validator = Validator::make($request->all(), [
                'name'         => 'required|string|max:60',
                'email'        => 'required|email|unique:users,email,' . $userId,
                'password'     => 'nullable|string|min:8',
                'role'         => 'required|in:worker,manager',
                'phone_number' => 'nullable|string|max:20',
                'address'      => 'nullable|string',
                'position'     => 'required|string|max:50',
                'base_salary'  => 'required|integer|min:0',
                'status'       => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $val = $validator->validated();

            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    $userData = [
                        'name'  => $val['name'],
                        'email' => $val['email'],
                        'role'  => $val['role'],
                    ];
                    if (!empty($val['password'])) {
                        $userData['password'] = Hash::make($val['password']);
                    }
                    $user->update($userData);
                }
            }

            $employee->update([
                'name'         => $val['name'],
                'phone_number' => $val['phone_number'] ?? null,
                'address'      => $val['address'] ?? null,
                'position'     => $val['position'],
                'base_salary'  => $val['base_salary'],
                'status'       => $val['status'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data karyawan berhasil diperbarui.'
            ], 200);

        } catch (HttpException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui data.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $employee = Employee::find($id);

            if (!$employee) {
                throw new HttpException(404, 'Data karyawan tidak ditemukan.');
            }

            $hasSalaryRecords = \App\Models\EmployeeSalary::where('fk_employee_id', $employee->id)->exists();

            if ($hasSalaryRecords) {
                throw new HttpException(400, 'Karyawan tidak dapat dihapus karena memiliki riwayat penggajian.');
            }

            $userId = $employee->fk_user_id;
            $employee->delete();

            if ($userId) {
                User::where('id', $userId)->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil dihapus.'
            ], 200);

        } catch (HttpException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data.', 'error' => $e->getMessage()], 500);
        }
    }
}
