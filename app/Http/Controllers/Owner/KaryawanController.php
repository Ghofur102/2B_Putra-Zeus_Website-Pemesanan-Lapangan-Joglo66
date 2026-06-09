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
        // Eager load untuk efisiensi query
        $employees = Employee::with('user')->get()->map(function ($emp) {
            return [
                'id'           => $emp->id,
                'user_id'      => $emp->fk_user_id,
                'name'         => $emp->name,
                'email'        => $emp->user->email ?? null,
                'role'         => $emp->user->role ?? null, // Mengembalikan null jika Non-Sistem
                'phone_number' => $emp->phone_number,
                'address'      => $emp->address,
                'position'     => $emp->position,
                'base_salary'  => $emp->base_salary,
                'join_date'    => $emp->join_date,
                'status'       => $emp->status,
                'is_system'    => $emp->fk_user_id !== null, // Flag boolean untuk frontend
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
            $isSystem = filter_var($request->is_system, FILTER_VALIDATE_BOOLEAN);

            // Validasi Dasar (Employee)
            $rules = [
                'name'         => 'required|string|max:60',
                'phone_number' => 'nullable|string|max:20',
                'address'      => 'nullable|string',
                'position'     => 'required|string|max:50',
                'base_salary'  => 'required|integer|min:0',
                'join_date'    => 'required|date',
                'is_system'    => 'required|boolean'
            ];

            // Validasi Tambahan jika Karyawan Sistem
            if ($isSystem) {
                $rules['email']    = 'required|email|unique:users,email';
                $rules['password'] = 'required|string|min:8';
                $rules['role']     = 'required|in:worker,treasurer';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $val = $validator->validated();
            $userId = null;

            // Injeksi ke tabel Users jika is_system true
            if ($isSystem) {
                $user = User::create([
                    'name'     => $val['name'],
                    'email'    => $val['email'],
                    'password' => Hash::make($val['password']),
                    'role'     => $val['role'],
                ]);
                $userId = $user->id;
            }

            $employee = Employee::create([
                'fk_user_id'   => $userId,
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
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan karyawan.', 'error' => $e->getMessage()], 500);
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

            $isSystem = filter_var($request->is_system, FILTER_VALIDATE_BOOLEAN);
            $userId = $employee->fk_user_id;

            $rules = [
                'name'         => 'required|string|max:60',
                'phone_number' => 'nullable|string|max:20',
                'address'      => 'nullable|string',
                'position'     => 'required|string|max:50',
                'base_salary'  => 'required|integer|min:0',
                'status'       => 'required|in:active,inactive',
                'is_system'    => 'required|boolean'
            ];

            if ($isSystem) {
                // Jika sebelumnya bukan akun sistem, paksa password isi. Jika sudah akun, opsional.
                $pwdRule = $userId ? 'nullable|string|min:8' : 'required|string|min:8';
                $rules['email']    = 'required|email|unique:users,email,' . $userId;
                $rules['password'] = $pwdRule;
                $rules['role']     = 'required|in:worker,treasurer';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
            }

            $val = $validator->validated();

            // Skenario Mutasi Akses Karyawan
            if ($isSystem && !$userId) {
                // Transisi: Non-Sistem -> Sistem (Buat Akun Baru)
                $user = User::create([
                    'name'     => $val['name'],
                    'email'    => $val['email'],
                    'password' => Hash::make($val['password']),
                    'role'     => $val['role'],
                ]);
                $userId = $user->id;
            } elseif ($isSystem && $userId) {
                // Update Karyawan Sistem
                $user = User::find($userId);
                $userData = ['name' => $val['name'], 'email' => $val['email'], 'role' => $val['role']];
                if (!empty($val['password'])) {
                    $userData['password'] = Hash::make($val['password']);
                }
                $user->update($userData);
            } elseif (!$isSystem && $userId) {
                // Transisi: Sistem -> Non-Sistem (Cabut Akses/Hapus Akun)
                User::where('id', $userId)->delete();
                $userId = null;
            }

            $employee->update([
                'fk_user_id'   => $userId,
                'name'         => $val['name'],
                'phone_number' => $val['phone_number'] ?? null,
                'address'      => $val['address'] ?? null,
                'position'     => $val['position'],
                'base_salary'  => $val['base_salary'],
                'status'       => $val['status'],
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data karyawan berhasil diperbarui.'], 200);

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

