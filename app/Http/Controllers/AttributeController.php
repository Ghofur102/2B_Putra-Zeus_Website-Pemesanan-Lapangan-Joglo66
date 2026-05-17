<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                    return $this->ok('Data atribut berhasil diambil.', []);
                }
                $query->whereIn('fk_field_id', $fieldIds);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('type', 'LIKE', "%{$search}%");
                });
            }

            return $this->ok('Data atribut berhasil diambil.', $query->latest()->get());
        } catch (\Exception $e) {
            return $this->fail('Gagal memuat data, silahkan coba lagi.');
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $attribute = Attribute::with('field:id,name')->find($id);

            if (!$attribute) {
                return $this->notFound('Data atribut tidak ditemukan.');
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return $this->forbidden('Anda tidak memiliki akses ke atribut ini.');
            }

            return $this->ok('Detail atribut berhasil diambil.', $attribute);
        } catch (\Exception $e) {
            return $this->fail('Gagal memuat data, silahkan coba lagi.');
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
            return $this->validationError($validator);
        }

        try {
            $user = $request->user();
            if (!$this->checkFieldAccess($user, $request->fk_field_id)) {
                return $this->forbidden('Anda tidak memiliki akses ke lapangan ini.');
            }

            $exists = Attribute::where('fk_field_id', $request->fk_field_id)
                ->where('name', $request->name)
                ->exists();

            if ($exists) {
                return $this->fail('Nama atribut sudah digunakan.', 422);
            }

            $attribute = Attribute::create([
                'fk_field_id' => $request->fk_field_id,
                'name' => $request->name,
                'type' => $request->type,
                'stock' => $request->stock,
                'price_hour' => $request->price_hour,
                'status' => 'active',
            ]);

            return $this->ok('Data atribut berhasil disimpan.', $attribute, 201);
        } catch (\Exception $e) {
            return $this->fail('Gagal menyimpan data, silahkan coba lagi.');
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
            return $this->validationError($validator);
        }

        try {
            $attribute = Attribute::find($id);

            if (!$attribute) {
                return $this->notFound('Data atribut tidak ditemukan.');
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return $this->forbidden('Anda tidak memiliki akses ke atribut ini.');
            }

            if ($request->filled('name') && $request->name !== $attribute->name) {
                $exists = Attribute::where('fk_field_id', $attribute->fk_field_id)
                    ->where('name', $request->name)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return $this->fail('Nama atribut sudah digunakan.', 422);
                }
            }

            $attribute->update($request->only([
                'name', 'type', 'stock', 'price_hour', 'status'
            ]));

            return $this->ok('Data atribut berhasil diperbarui.', $attribute->fresh());
        } catch (\Exception $e) {
            return $this->fail('Gagal menyimpan data, silahkan coba lagi.');
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $attribute = Attribute::find($id);

            if (!$attribute) {
                return $this->notFound('Data atribut tidak ditemukan.');
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return $this->forbidden('Anda tidak memiliki akses ke atribut ini.');
            }

            $attribute->delete();

            return $this->ok('Data atribut berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->fail('Gagal menghapus data, silahkan coba lagi.');
        }
    }

    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $attribute = Attribute::find($id);

            if (!$attribute) {
                return $this->notFound('Data atribut tidak ditemukan.');
            }

            $user = $request->user();
            if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return $this->forbidden('Anda tidak memiliki akses ke atribut ini.');
            }

            $newStatus = $attribute->status === 'active' ? 'inactive' : 'active';
            $attribute->update(['status' => $newStatus]);

            return $this->ok(
                $newStatus === 'active' ? 'Atribut berhasil diaktifkan.' : 'Atribut berhasil dinonaktifkan.',
                $attribute->fresh()
            );
        } catch (\Exception $e) {
            return $this->fail('Gagal mengubah status, silahkan coba lagi.');
        }
    }
}
