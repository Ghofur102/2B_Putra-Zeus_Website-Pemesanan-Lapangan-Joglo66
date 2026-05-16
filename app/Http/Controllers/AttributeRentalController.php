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
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        try {
            $user = $request->user();

            foreach ($request->items as $item) {
                $attribute = Attribute::find($item['fk_attribute_id']);
                if (!$attribute) {
                    return $this->notFound('Atribut tidak ditemukan.');
                }
                if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                    return $this->forbidden('Anda tidak memiliki akses ke atribut ini.');
                }
                if ($attribute->status === 'inactive') {
                    return $this->fail("Atribut {$attribute->name} sedang tidak tersedia.", 422);
                }
                if ($attribute->stock < $item['quantity']) {
                    return $this->fail(
                        "Stok tidak mencukupi. Sisa stok {$attribute->name}: {$attribute->stock}",
                        422
                    );
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

            return $this->ok('Transaksi penyewaan berhasil disimpan.', [
                'items' => $rentals,
                'total_price' => collect($rentals)->sum('total'),
                'customer_name' => $request->customer_name,
                'transaction_date' => $request->transaction_date,
            ], 201);
        } catch (\Exception $e) {
            return $this->fail('Sistem sedang sibuk. Silahkan coba lagi.');
        }
    }

    public function returnItem(Request $request, $id): JsonResponse
    {
        try {
            $rental = BookingAttribute::find($id);

            if (!$rental) {
                return $this->notFound('Data penyewaan tidak ditemukan.');
            }

            if ($rental->status === 'dikembalikan') {
                return $this->fail('Atribut ini sudah dikembalikan.', 422);
            }

            $user = $request->user();
            $attribute = Attribute::find($rental->fk_attribute_id);
            if ($attribute && !$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                return $this->forbidden('Anda tidak memiliki akses ke atribut ini.');
            }

            DB::transaction(function () use ($rental, $attribute) {
                $rental->update(['status' => 'dikembalikan']);

                if ($attribute) {
                    $attribute->increment('stock', $rental->quantity);
                }
            });

            return $this->ok('Atribut berhasil dikembalikan.', $rental->fresh()->load('attribute:id,name,type'));
        } catch (\Exception $e) {
            return $this->fail('Gagal memproses pengembalian, silahkan coba lagi.');
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
                    return $this->ok('Data riwayat berhasil diambil.', []);
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

            return $this->ok('Data riwayat penyewaan berhasil diambil.', $query->latest()->paginate($request->limit ?? 20));
        } catch (\Exception $e) {
            return $this->fail('Gagal memuat data, silahkan coba lagi.');
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $rental = BookingAttribute::with('attribute:id,name,type,price_hour,stock,fk_field_id')->find($id);

            if (!$rental) {
                return $this->notFound('Data penyewaan tidak ditemukan.');
            }

            return $this->ok('Detail penyewaan berhasil diambil.', $rental);
        } catch (\Exception $e) {
            return $this->fail('Gagal memuat data, silahkan coba lagi.');
        }
    }
}
