<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Traits\FieldAccessTrait;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

class AttributeController extends Controller
{
    use FieldAccessTrait;

    private const NOT_FOUND_MSG = 'Data atribut tidak ditemukan.';
    private const FORBIDDEN_MSG = 'Anda tidak memiliki akses ke atribut ini.';

    public function index(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $user = $request->user();
            $search = $request->search;

            $query = Attribute::with('field:id,name');

            if ($user && $user->role === 'worker') {
                $fieldIds = $this->getAccessibleFieldIds($user);
                if (empty($fieldIds)) {
                    $data = ['success' => true, 'message' => 'Data atribut kosong.', 'data' => []];
                    return response()->json($data, $status);
                }
                $query->whereIn('fk_field_id', $fieldIds);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('type', 'LIKE', "%{$search}%");
                });
            }

            $data = [
                'success' => true,
                'message' => 'Data atribut berhasil diambil.',
                'data' => $query->latest()->get()
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal memuat data: ' . $e->getMessage()];
        }
        return response()->json($data, $status);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $status = 200;
        try {
            $attribute = Attribute::with('field:id,name')->find($id);

            if (!$attribute) {
                throw new NotFoundHttpException(self::NOT_FOUND_MSG);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                throw new AccessDeniedHttpException(self::FORBIDDEN_MSG);
            }

            $data = ['success' => true, 'message' => 'Detail atribut berhasil diambil.', 'data' => $attribute];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal memuat data: ' . $e->getMessage()];
        }
        return response()->json($data, $status);
    }

    public function store(Request $request): JsonResponse
    {
        $status = 201;
        try {
            $validator = Validator::make($request->all(), [
                'fk_field_id' => 'required|exists:fields,id',
                'name' => 'required|string|max:100',
                'type' => 'required|in:sepatu,rompi,lainnya',
                'stock' => 'required|integer|min:0',
                'price_hour' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                $status = 422;
                $data = ['success' => false, 'message' => 'Validasi gagal.', 'errors' => $validator->errors()];
                return response()->json($data, $status);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $request->fk_field_id)) {
                throw new AccessDeniedHttpException('Anda tidak memiliki akses ke lapangan ini.');
            }

            $exists = Attribute::where('fk_field_id', $request->fk_field_id)->where('name', $request->name)->exists();

            if ($exists) {
                throw new UnprocessableEntityHttpException('Nama atribut sudah digunakan.');
            }

            $attribute = Attribute::create([
                'fk_field_id' => $request->fk_field_id,
                'name' => $request->name,
                'type' => $request->type,
                'stock' => $request->stock,
                'price_hour' => $request->price_hour,
                'status' => 'active',
            ]);

            $data = ['success' => true, 'message' => 'Data atribut berhasil disimpan.', 'data' => $attribute];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()];
        }
        return response()->json($data, $status);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $status = 200;
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:100',
                'type' => 'sometimes|in:sepatu,rompi,lainnya',
                'stock' => 'sometimes|integer|min:0',
                'price_hour' => 'sometimes|integer|min:0',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                $status = 422;
                $data = ['success' => false, 'message' => 'Validasi gagal.', 'errors' => $validator->errors()];
                return response()->json($data, $status);
            }

            $attribute = Attribute::find($id);

            if (!$attribute) {
                throw new NotFoundHttpException(self::NOT_FOUND_MSG);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                throw new AccessDeniedHttpException(self::FORBIDDEN_MSG);
            }

            if ($request->filled('name') && $request->name !== $attribute->name) {
                $exists = Attribute::where('fk_field_id', $attribute->fk_field_id)
                    ->where('name', $request->name)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    throw new UnprocessableEntityHttpException('Nama atribut sudah digunakan.');
                }
            }

            $attribute->update($request->only(['name', 'type', 'stock', 'price_hour', 'status']));

            $data = ['success' => true, 'message' => 'Data atribut berhasil diperbarui.', 'data' => $attribute->fresh()];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()];
        }
        return response()->json($data, $status);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $status = 200;
        try {
            $attribute = Attribute::find($id);

            if (!$attribute) {
                throw new NotFoundHttpException(self::NOT_FOUND_MSG);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                throw new AccessDeniedHttpException(self::FORBIDDEN_MSG);
            }

            $attribute->delete();

            $data = ['success' => true, 'message' => 'Data atribut berhasil dihapus.'];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (QueryException $e) {
            $status = ($e->getCode() == "23000") ? 400 : 500;
            $msg = ($e->getCode() == "23000")
                ? 'Atribut tidak dapat dihapus karena masih digunakan pada data penyewaan atau transaksi.'
                : 'Gagal menghapus data (Error Database).';
            $data = ['success' => false, 'message' => $msg];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
        return response()->json($data, $status);
    }

    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $status = 200;
        try {
            $attribute = Attribute::find($id);

            if (!$attribute) {
                throw new NotFoundHttpException(self::NOT_FOUND_MSG);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                throw new AccessDeniedHttpException(self::FORBIDDEN_MSG);
            }

            $newStatus = $attribute->status === 'active' ? 'inactive' : 'active';
            $attribute->update(['status' => $newStatus]);

            $msg = ($newStatus === 'active') ? 'Atribut diaktifkan.' : 'Atribut dinonaktifkan.';
            $data = [
                'success' => true,
                'message' => $msg,
                'data' => $attribute->fresh()
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal mengubah status: ' . $e->getMessage()];
        }
        return response()->json($data, $status);
    }
}
