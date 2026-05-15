<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\BookingAttribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttributeRentalController extends Controller
{
    use \App\Http\Controllers\Traits\FieldAccessTrait;

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.fk_attribute_id' => 'required|exists:attributes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'duration_hours' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
        ], [
            'items.required' => 'Pilih minimal satu atribut.',
            'items.*.quantity.required' => 'Jumlah atribut wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah atribut harus berupa angka.',
            'items.*.quantity.min' => 'Jumlah atribut minimal 1.',
            'customer_name.required' => 'Nama penyewa wajib diisi.',
            'duration_hours.required' => 'Durasi sewa wajib diisi.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
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

            foreach ($request->items as $item) {
                $attribute = Attribute::find($item['fk_attribute_id']);
                if (!$attribute) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Atribut tidak ditemukan.',
                        'data' => null
                    ], 404);
                }
                if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Forbidden. Anda tidak memiliki akses ke atribut ini.',
                        'data' => null
                    ], 403);
                }
                if ($attribute->status === 'inactive') {
                    return response()->json([
                        'success' => false,
                        'message' => "Atribut {$attribute->name} sedang tidak tersedia.",
                        'data' => null
                    ], 422);
                }
                if ($attribute->stock < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stok tidak mencukupi. Sisa stok {$attribute->name}: {$attribute->stock}",
                        'data' => null
                    ], 422);
                }
            }

            $rentals = DB::transaction(function () use ($request) {
                $created = [];

                foreach ($request->items as $item) {
                    $attribute = Attribute::findOrFail($item['fk_attribute_id']);

                    $attribute->decrement('stock', $item['quantity']);

                    $total = $attribute->price_hour * $item['quantity'] * $request->duration_hours;

                    $rental = BookingAttribute::create([
                        'fk_booking_id' => null,
                        'fk_attribute_id' => $item['fk_attribute_id'],
                        'quantity' => $item['quantity'],
                        'price' => $attribute->price_hour,
                        'total' => $total,
                        'transaction_date' => $request->transaction_date,
                        'status' => 'dipinjam',
                        'customer_name' => $request->customer_name,
                        'customer_phone' => $request->customer_phone,
                        'duration_hours' => $request->duration_hours,
                    ]);

                    $rental->load('attribute:id,name,type');
                    $created[] = $rental;
                }

                return $created;
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaksi penyewaan berhasil disimpan.',
                'data' => [
                    'items' => $rentals,
                    'total_price' => collect($rentals)->sum('total'),
                    'customer_name' => $request->customer_name,
                    'transaction_date' => $request->transaction_date,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sistem sedang sibuk. Silahkan coba lagi.',
                'data' => null
            ], 500);
        }
    }

    public function returnItem(Request $request, $id): JsonResponse
    {
        try {
            $rental = BookingAttribute::find($id);

            if (!$rental) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data penyewaan tidak ditemukan.',
                    'data' => null
                ], 404);
            }

            if ($rental->status === 'dikembalikan') {
                return response()->json([
                    'success' => false,
                    'message' => 'Atribut ini sudah dikembalikan.',
                    'data' => null
                ], 422);
            }

            $user = $request->user();
            $attribute = Attribute::find($rental->fk_attribute_id);
            if ($attribute && !$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Anda tidak memiliki akses ke atribut ini.',
                    'data' => null
                ], 403);
            }

            DB::transaction(function () use ($rental, $attribute) {
                $rental->update(['status' => 'dikembalikan']);

                if ($attribute) {
                    $attribute->increment('stock', $rental->quantity);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Atribut berhasil dikembalikan.',
                'data' => $rental->fresh()->load('attribute:id,name,type')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pengembalian, silahkan coba lagi.',
                'data' => null
            ], 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $search = $request->search;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $status = $request->status;

            $query = BookingAttribute::with('attribute:id,name,type,fk_field_id');

            if ($user && $user->role === 'worker') {
                $fieldIds = $this->getAccessibleFieldIds($user);
                if (empty($fieldIds)) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Data riwayat berhasil diambil.',
                        'data' => []
                    ], 200);
                }
                $query->whereHas('attribute', function ($q) use ($fieldIds) {
                    $q->whereIn('fk_field_id', $fieldIds);
                });
            }

            if ($search) {
                $query->where('customer_name', 'LIKE', "%{$search}%");
            }

            if ($startDate) {
                $query->whereDate('transaction_date', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('transaction_date', '<=', $endDate);
            }

            if ($status) {
                $query->where('status', $status);
            }

            $rentals = $query->latest()->paginate($request->limit ?? 20);

            return response()->json([
                'success' => true,
                'message' => 'Data riwayat penyewaan berhasil diambil.',
                'data' => $rentals
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data, silahkan coba lagi.',
                'data' => null
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $rental = BookingAttribute::with('attribute:id,name,type,price_hour,stock,fk_field_id')->find($id);

            if (!$rental) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data penyewaan tidak ditemukan.',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail penyewaan berhasil diambil.',
                'data' => $rental
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data, silahkan coba lagi.',
                'data' => null
            ], 500);
        }
    }
}
