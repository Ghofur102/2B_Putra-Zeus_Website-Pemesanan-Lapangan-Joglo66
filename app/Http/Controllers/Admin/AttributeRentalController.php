<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attribute;
use App\Models\BookingAttribute;
use App\Models\BookingDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Http\Controllers\Traits\FieldAccessTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AttributeRentalController extends Controller
{
    use FieldAccessTrait;

    private const ACCESS_DENIED_MSG = 'Anda tidak memiliki akses ke atribut ini.';
    private const RENTAL_NOT_FOUND_MSG = 'Data penyewaan tidak ditemukan.';

    public function getActiveBookings(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $user = $request->user();
            
            $query = BookingDetail::with(['booking.user', 'booking.field'])
                ->whereNotIn('status', ['cancelled', 'closed field cancelled', 'finish'])
                ->whereDate('play_date', '>=', Carbon::now()->toDateString());

            if ($user && $user->role === 'worker') {
                $fieldIds = $this->getAccessibleFieldIds($user);
                $query->whereHas('booking', function ($q) use ($fieldIds) {
                    $q->whereIn('fk_field_id', $fieldIds);
                });
            }

            $bookings = $query->orderBy('play_date')->orderBy('start_play_time')->get()->map(function ($detail) {
                return [
                    'detail_id' => $detail->id,
                    'booking_id' => $detail->fk_booking_id,
                    'field_id' => $detail->booking->fk_field_id,
                    'field_name' => $detail->booking->field->name ?? 'Unknown',
                    'team_name' => $detail->booking->team_name ?? 'Unknown',
                    'customer_name' => $detail->booking->user->name ?? 'Guest',
                    'customer_phone' => $detail->booking->customer_phone ?? '',
                    'play_date' => Carbon::parse($detail->play_date)->format('Y-m-d'),
                    'start_time' => Carbon::parse($detail->start_play_time)->format('H:i'),
                    'end_time' => Carbon::parse($detail->end_play_time)->format('H:i'),
                ];
            });

            $data = ['success' => true, 'message' => 'Data booking aktif berhasil diambil.', 'data' => $bookings];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal memuat data booking aktif.'];
        }
        return response()->json($data, $status);
    }

    public function store(Request $request): JsonResponse
    {
        $status = 201;
        try {
            $validator = Validator::make($request->all(), [
                'fk_booking_id' => 'required|exists:bookings,id',
                'items' => 'required|array|min:1',
                'items.*.fk_attribute_id' => 'required|exists:attributes,id',
                'items.*.quantity' => 'required|integer|min:1',
                'customer_name' => 'required|string|max:100',
                'customer_phone' => 'nullable|string|max:20',
                'duration_hours' => 'required|integer|min:1',
                'transaction_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                $status = 422;
                $data = ['success' => false, 'message' => 'Validasi gagal.', 'errors' => $validator->errors()];
                return response()->json($data, $status);
            }

            $user = $request->user();

            foreach ($request->items as $item) {
                $attribute = Attribute::find($item['fk_attribute_id']);
                if (!$attribute) {
                    throw new HttpException(404, 'Atribut tidak ditemukan.');
                }
                if (!$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                    throw new HttpException(403, self::ACCESS_DENIED_MSG);
                }
                if ($attribute->status === 'inactive') {
                    throw new HttpException(422, "Atribut {$attribute->name} sedang tidak tersedia.");
                }
                if ($attribute->stock < $item['quantity']) {
                    throw new HttpException(422, "Stok tidak mencukupi. Sisa stok {$attribute->name}: {$attribute->stock}");
                }
            }

            $rentals = DB::transaction(function () use ($request) {
                $created = [];

                foreach ($request->items as $item) {
                    $attribute = Attribute::findOrFail($item['fk_attribute_id']);
                    $attribute->decrement('stock', $item['quantity']);

                    $total = $attribute->price_hour * $item['quantity'] * $request->duration_hours;

                    $rental = BookingAttribute::create([
                        'fk_booking_id' => $request->fk_booking_id,
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

            $data = [
                'success' => true,
                'message' => 'Transaksi penyewaan berhasil disimpan.',
                'data' => [
                    'items' => $rentals,
                    'total_price' => collect($rentals)->sum('total'),
                    'customer_name' => $request->customer_name,
                    'transaction_date' => $request->transaction_date,
                ]
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Sistem sedang sibuk. Silahkan coba lagi.'];
        }
        return response()->json($data, $status);
    }

    public function returnItem(Request $request, $id): JsonResponse
    {
        $status = 200;
        try {
            $rental = BookingAttribute::find($id);

            if (!$rental) {
                throw new HttpException(404, self::RENTAL_NOT_FOUND_MSG);
            }

            if ($rental->status === 'dikembalikan') {
                throw new HttpException(422, 'Atribut ini sudah dikembalikan.');
            }

            $user = $request->user();
            $attribute = Attribute::find($rental->fk_attribute_id);
            if ($attribute && !$this->checkFieldAccess($user, $attribute->fk_field_id)) {
                throw new HttpException(403, self::ACCESS_DENIED_MSG);
            }

            DB::transaction(function () use ($rental, $attribute) {
                $rental->update(['status' => 'dikembalikan']);
                if ($attribute) {
                    $attribute->increment('stock', $rental->quantity);
                }
            });

            $data = [
                'success' => true,
                'message' => 'Atribut berhasil dikembalikan.',
                'data' => $rental->fresh()->load('attribute:id,name,type')
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal memproses pengembalian, silahkan coba lagi.'];
        }
        return response()->json($data, $status);
    }

    public function history(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $user = $request->user();
            $search = $request->search;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $statusFilter = $request->status;

            $query = BookingAttribute::with('attribute:id,name,type,fk_field_id');

            if ($user && $user->role === 'worker') {
                $fieldIds = $this->getAccessibleFieldIds($user);
                if (empty($fieldIds)) {
                    $data = ['success' => true, 'message' => 'Data riwayat berhasil diambil.', 'data' => []];
                    return response()->json($data, $status);
                }
                $query->whereIn('fk_field_id', $fieldIds);
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

            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }

            $data = [
                'success' => true,
                'message' => 'Data riwayat penyewaan berhasil diambil.',
                'data' => $query->latest()->paginate($request->limit ?? 20)
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal memuat data, silahkan coba lagi.'];
        }
        return response()->json($data, $status);
    }

    public function show($id): JsonResponse
    {
        $status = 200;
        try {
            $rental = BookingAttribute::with('attribute:id,name,type,price_hour,stock,fk_field_id')->find($id);

            if (!$rental) {
                throw new HttpException(404, self::RENTAL_NOT_FOUND_MSG);
            }

            $data = ['success' => true, 'message' => 'Detail penyewaan berhasil diambil.', 'data' => $rental];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Gagal memuat data, silahkan coba lagi.'];
        }
        return response()->json($data, $status);
    }
}
