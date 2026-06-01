# Route Tambahan - Developer: Huda

Tambahkan kode berikut ke `routes/api.php` oleh yang berhak mengubah file tersebut.

---

## 1. Import (tambahkan di bagian atas api.php bersama `use` statement lainnya)

```php
use App\Http\Controllers\Admin\RekapitulasiController;
use App\Http\Controllers\Admin\LaporanController;
```

---

## 2. Route (tambahkan di dalam `Route::prefix('admin')->middleware([...])->group(function () { ... })`)

```php
// DEVELOPER: Huda
// Rekap transaksi harian — GET /api/admin/rekap-harian?tanggal=Y-m-d
Route::get('/rekap-harian', [RekapitulasiController::class, 'index']);

// Laporan neraca keuangan bulanan — GET /api/admin/laporan-bulanan?bulan=1&tahun=2026
Route::get('/laporan-bulanan', [LaporanController::class, 'index']);
```

---

## Catatan
- Kedua route menggunakan middleware yang sudah ada: `auth:sanctum` dan `check.field.admin`
- `/laporan-bulanan` hanya bisa diakses oleh user dengan `role = bendahara` (dicek di dalam controller)
- Controller yang perlu di-push: `RekapitulasiController.php` dan `LaporanController.php` di folder `app/Http/Controllers/Admin/`