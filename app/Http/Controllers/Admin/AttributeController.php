<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Traits\FieldAccessTrait;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;

class AttributeController extends Controller
{
    use FieldAccessTrait;

    public function index(Request $request): JsonResponse
    {
        try { 
            $user = $request->user();
            $search = $request->search;

            $query = Attribute::with('field:id,name');

            if ($user && $user->role === 'worker') {
                $fieldIds = $this->getAccessibleFieldIds($user);
                if (empty($fieldIds)) {
                    return response()->json(['success' => true, 'message' => 'Data atribut kosong.', 'data' => []]);
                }
                $query->whereIn('fk_field_id', $fieldIds);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('type', 'LIKE', "%{$search}%");
                });
            }

            return response()->json([
                'success' => true, 
                'message' => 'Data atribut berhasil diambil.', 
                'data' => $query->latest()->get()
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memuat data: ' . $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $attribute = Attribute::with('field:id,name')->find($id);

            if (!$attribute) {
                return response()->json(['success' => false, 'message' => 'Data atribut tidak ditemukan.'], 404);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke atribut ini.'], 403);
            }

            return response()->json(['success' => true, 'message' => 'Detail atribut berhasil diambil.', 'data' => $attribute]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memuat data: ' . $e->getMessage()], 500);
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
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        try {
            $user = $request->user();
            if (!$this->checkFieldAccess($user, $request->fk_field_id)) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke lapangan ini.'], 403);
            }

            $exists = Attribute::where('fk_field_id', $request->fk_field_id)->where('name', $request->name)->exists();

            if ($exists) {
                return response()->json(['success' => false, 'message' => 'Nama atribut sudah digunakan.'], 422);
            }

            $attribute = Attribute::create([
                'fk_field_id' => $request->fk_field_id,
                'name' => $request->name,
                'type' => $request->type,
                'stock' => $request->stock,
                'price_hour' => $request->price_hour,
                'status' => 'active',
            ]);

            return response()->json(['success' => true, 'message' => 'Data atribut berhasil disimpan.', 'data' => $attribute], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
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
            return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        try {
            $attribute = Attribute::find($id);

            if (!$attribute) {
                return response()->json(['success' => false, 'message' => 'Data atribut tidak ditemukan.'], 404);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke atribut ini.'], 403);
            }

            if ($request->filled('name') && $request->name !== $attribute->name) {
                $exists = Attribute::where('fk_field_id', $attribute->fk_field_id)
                    ->where('name', $request->name)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response()->json(['success' => false, 'message' => 'Nama atribut sudah digunakan.'], 422);
                }
            }

            $attribute->update($request->only(['name', 'type', 'stock', 'price_hour', 'status']));

            return response()->json(['success' => true, 'message' => 'Data atribut berhasil diperbarui.', 'data' => $attribute->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $attribute = Attribute::find($id);

            if (!$attribute) {
                return response()->json(['success' => false, 'message' => 'Data atribut tidak ditemukan.'], 404);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke atribut ini.'], 403);
            }

            $attribute->delete();

            return response()->json(['success' => true, 'message' => 'Data atribut berhasil dihapus.']);
            
        } catch (QueryException $e) {
            // Menangkap error jika atribut masih terikat relasi Foreign Key dengan tabel lain
            if ($e->getCode() == "23000") {
                return response()->json(['success' => false, 'message' => 'Atribut tidak dapat dihapus karena masih digunakan pada data penyewaan atau transaksi.'], 400);
            }
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data (Error Database).'], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $attribute = Attribute::find($id);

            if (!$attribute) {
                return response()->json(['success' => false, 'message' => 'Data atribut tidak ditemukan.'], 404);
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke atribut ini.'], 403);
            }

            $newStatus = $attribute->status === 'active' ? 'inactive' : 'active';
            $attribute->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => $newStatus === 'active' ? 'Atribut diaktifkan.' : 'Atribut dinonaktifkan.',
                'data' => $attribute->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengubah status: ' . $e->getMessage()], 500);
        }
    }
}