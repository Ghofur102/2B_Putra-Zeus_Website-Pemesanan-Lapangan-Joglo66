<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FieldController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Tenant\Payment\DuitkuController;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeRentalController;

// Payment Gateway
Route::post('/duitku/callback', [DuitkuController::class, 'callback']);

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
    Route::get('/profile', [AuthController::class, 'profile']);

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
    Route::post('/refund-overpayment/{id}', [BookingController::class, 'refundOverpayment']); // Ghofur

    // Payment
    Route::post('/payment-booking', [PaymentController::class, 'processPayment']); // Danil

    // Attribute
    Route::get('/list-attribute', [AttributeController::class, 'index']);
    Route::get('/detail-attribute/{id}', [AttributeController::class, 'show']);
    Route::post('/create-attribute', [AttributeController::class, 'store']);
    Route::post('/update-attribute/{id}', [AttributeController::class, 'update']);
    Route::post('/delete-attribute/{id}', [AttributeController::class, 'destroy']);
    Route::post('/toggle-attribute-status/{id}', [AttributeController::class, 'toggleStatus']);
    Route::get('/active-bookings', [AttributeRentalController::class, 'getActiveBookings']);

    // Attribute Rental
    Route::post('/rent-attribute', [AttributeRentalController::class, 'store']);
    Route::post('/return-rent-attribute/{id}', [AttributeRentalController::class, 'returnItem']);
    Route::get('/detail-rent-attribute/{id}', [AttributeRentalController::class, 'show']);
    Route::get('/history-rent-attribute', [AttributeRentalController::class, 'history']);

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });
});

