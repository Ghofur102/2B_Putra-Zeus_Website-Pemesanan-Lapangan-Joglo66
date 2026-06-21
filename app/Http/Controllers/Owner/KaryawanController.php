<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Http\Requests\Owner\StoreEmployeeRequest;
use App\Http\Requests\Owner\UpdateEmployeeRequest;
use App\Services\Owner\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class KaryawanController extends Controller
{
    protected EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function index(): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $employees = $this->employeeService->getEmployeesList();
            $data = [
                'success' => true,
                'data'    => $employees
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Gagal memuat data karyawan.'
            ];
        }

        return response()->json($data, $status);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $status = 201;
        $data = [];

        try {
            $employee = $this->employeeService->storeEmployee($request->validated());
            $data = [
                'success' => true,
                'message' => 'Karyawan berhasil ditambahkan.',
                'data'    => $employee
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Gagal menambahkan karyawan.',
                'error'   => $e->getMessage()
            ];
        }

        return response()->json($data, $status);
    }

    public function update(UpdateEmployeeRequest $request, $id): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $employee = Employee::query()->find($id);
            if (!$employee) {
                throw new NotFoundHttpException('Data karyawan tidak ditemukan.');
            }

            $this->employeeService->updateEmployee($employee, $request->validated());
            $data = [
                'success' => true,
                'message' => 'Data karyawan berhasil diperbarui.'
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Gagal memperbarui data.',
                'error'   => $e->getMessage()
            ];
        }

        return response()->json($data, $status);
    }

    public function destroy($id): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $employee = Employee::query()->find($id);
            if (!$employee) {
                throw new NotFoundHttpException('Data karyawan tidak ditemukan.');
            }

            $this->employeeService->destroyEmployee($employee);
            $data = [
                'success' => true,
                'message' => 'Karyawan berhasil dihapus.'
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Gagal menghapus data.',
                'error'   => $e->getMessage()
            ];
        }

        return response()->json($data, $status);
    }
}
