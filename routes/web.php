<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\Payment\PaymentController;

Route::get('/payment',                   [PaymentController::class, 'index'])->name('payment.index');
Route::post('/payment',                  [PaymentController::class, 'store'])->name('payment.store');
Route::get('/payment/return',            [PaymentController::class, 'tripayReturn'])->name('payment.return');
Route::post('/payment/callback',         [PaymentController::class, 'tripayCallback'])->name('payment.callback');
Route::get('/payment/dummy/checkout',    [PaymentController::class, 'dummyCheckout'])->name('payment.dummy.checkout');
Route::post('/payment/dummy/simulate',   [PaymentController::class, 'dummySimulate'])->name('payment.dummy.simulate');
Route::get('/test-status/{reference}',   [PaymentController::class, 'testStatus'])->name('payment.test.status');
Route::get('/status',                    [PaymentController::class, 'status'])->name('status.index');
Route::get('/bukti',                     [PaymentController::class, 'bukti'])->name('bukti.index');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/payment/status', [PaymentController::class, 'status'])->name('payment.status');
Route::get('/payment/bukti',  [PaymentController::class, 'bukti'])->name('payment.bukti');

Route::middleware(['auth'])->group(function () {
    // Route::get('/payment', [PaymentController::class, 'index']); // Commented for testing without login
});

Route::get('/metrics', function () {
    return response("
# HELP app_status Application status
# TYPE app_status gauge
app_status 1

# HELP memory_usage Memory usage in bytes
# TYPE memory_usage gauge
memory_usage " . memory_get_usage()
    , 200)
    ->header('Content-Type', 'text/plain; version=0.0.4');
});