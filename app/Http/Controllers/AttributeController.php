<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttributeController extends Controller
{
    use \App\Http\Controllers\Traits\FieldAccessTrait;

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $search = $request->search;

            $query = Attribute::with('field:id,name');

            if ($user && $user->role === 'worker') {
                $fieldIds = $this->getAccessibleFieldIds($user);
                if (empty($fieldIds)) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Data atribut berhasil diambil.',
                        'data' => []
                    ], 200);
                }
                $query->whereIn('fk_field_id', $fieldIds);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('type', 'LIKE', "%{$search}%");
                });
            }

            $attributes = $query->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Data atribut berhasil diambil.',
                'data' => $attributes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data, silahkan coba lagi.',
                'data' => null
            ], 500);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $attribute = Attribute::with('field:id,name')->find($id);

            if (!$attribute) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data atribut tidak ditemukan.',
                    'data' => null
                ], 404);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Anda tidak memiliki akses ke atribut ini.',
                    'data' => null
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail atribut berhasil diambil.',
                'data' => $attribute
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data, silahkan coba lagi.',
                'data' => null
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fk_field_id' => 'required|exists:fields,id',
            'name' => 'required|string|max:100',
            'type' => 'required|in:sepatu,rompi,lainnya',
            'stock' => 'required|integer|min:0',
            'price_hour' => 'required|integer|min:0',
        ], [
            'name.required' => 'Nama atribut wajib diisi.',
            'type.required' => 'Jenis atribut wajib dipilih.',
            'type.in' => 'Jenis atribut tidak valid.',
            'stock.required' => 'Stok wajib diisi.',
            'stock.integer' => 'Format input harus berupa angka.',
            'stock.min' => 'Stok tidak boleh negatif.',
            'price_hour.required' => 'Harga sewa wajib diisi.',
            'price_hour.integer' => 'Format input harus berupa angka.',
            'price_hour.min' => 'Harga sewa tidak boleh negatif.',
            'fk_field_id.required' => 'Lapangan wajib dipilih.',
            'fk_field_id.exists' => 'Lapangan tidak ditemukan.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            if (!$this->checkFieldAccess($user, $request->fk_field_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Anda tidak memiliki akses ke lapangan ini.',
                    'data' => null
                ], 403);
            }

            $exists = Attribute::where('fk_field_id', $request->fk_field_id)
                ->where('name', $request->name)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nama atribut sudah digunakan.',
                    'data' => null
                ], 422);
            }

            $attribute = Attribute::create([
                'fk_field_id' => $request->fk_field_id,
                'name' => $request->name,
                'type' => $request->type,
                'stock' => $request->stock,
                'price_hour' => $request->price_hour,
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data atribut berhasil disimpan.',
                'data' => $attribute
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data, silahkan coba lagi.',
                'data' => null
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|in:sepatu,rompi,lainnya',
            'stock' => 'sometimes|integer|min:0',
            'price_hour' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $attribute = Attribute::find($id);

            if (!$attribute) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data atribut tidak ditemukan.',
                    'data' => null
                ], 404);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Anda tidak memiliki akses ke atribut ini.',
                    'data' => null
                ], 403);
            }

            if ($request->filled('name') && $request->name !== $attribute->name) {
                $exists = Attribute::where('fk_field_id', $attribute->fk_field_id)
                    ->where('name', $request->name)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nama atribut sudah digunakan.',
                        'data' => null
                    ], 422);
                }
            }

            $attribute->update($request->only([
                'name', 'type', 'stock', 'price_hour', 'status'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Data atribut berhasil diperbarui.',
                'data' => $attribute->fresh()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data, silahkan coba lagi.',
                'data' => null
            ], 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $attribute = Attribute::find($id);

            if (!$attribute) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data atribut tidak ditemukan.',
                    'data' => null
                ], 404);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Anda tidak memiliki akses ke atribut ini.',
                    'data' => null
                ], 403);
            }

            $attribute->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data atribut berhasil dihapus.',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data, silahkan coba lagi.',
                'data' => null
            ], 500);
        }
    }

    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $attribute = Attribute::find($id);

            if (!$attribute) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data atribut tidak ditemukan.',
                    'data' => null
                ], 404);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Anda tidak memiliki akses ke atribut ini.',
                    'data' => null
                ], 403);
            }

            $newStatus = $attribute->status === 'active' ? 'inactive' : 'active';
            $attribute->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => $newStatus === 'active'
                    ? 'Atribut berhasil diaktifkan.'
                    : 'Atribut berhasil dinonaktifkan.',
                'data' => $attribute->fresh()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status, silahkan coba lagi.',
                'data' => null
            ], 500);
        }
    }
}
