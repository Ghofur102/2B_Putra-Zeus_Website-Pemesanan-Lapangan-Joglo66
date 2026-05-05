<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;

// Public test endpoint (no authentication required)
Route::get('/hello', function () {
    return response()->json(['message' => 'Hello from Laravel!']);
});

Route::post('/login', [AuthController::class, 'login']);

Route::prefix('admin')->middleware(['auth:sanctum', 'check.field.admin'])->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'dashboard']); // Zami

    // Field
    Route::get('/list-field', [FieldController::class, 'index']); // Zami
    Route::get('/detail-field/{field_id}', [FieldController::class, 'show']); // Ghofur
    Route::post('/update-field', [FieldController::class, 'update']); // Huda
    Route::get('/check-slot-availability/{field_id}/{date}', [FieldController::class, 'checkAvailability']); // Huda
    Route::post('/close-field', [FieldController::class, 'closeField']); // Huda

    // Booking
    Route::get('/list-booking', [BookingController::class, 'index']); // Zami
    Route::post('/create-booking', [BookingController::class, 'store']); // Danil
    Route::get('/detail-booking/{detail_booking_id}', [BookingController::class, 'show']); // Ghofur
    Route::post('/reschedule-booking/{detail_booking_id}', [BookingController::class, 'reschedule']); // Ghofur
    Route::post('/cancel-booking/{detail_booking_id}', [BookingController::class, 'cancel']); // Ghofur
    Route::get('/list-close-booking', [BookingController::class, 'closedBookings']); // Huda

    // Payment
    Route::post('/payment-booking', [PaymentController::class, 'processPayment']); // Danil

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::post('/tripay/callback', [\App\Http\Controllers\Tenant\Payment\PaymentController::class, 'tripayCallback']);

