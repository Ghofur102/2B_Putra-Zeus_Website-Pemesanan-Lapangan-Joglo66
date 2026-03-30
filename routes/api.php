<?php

use App\Http\Controllers\BookingsController;
use App\Http\Controllers\FieldClosuresController;
use App\Http\Controllers\PaymentsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Field Closures Controller
Route::get('/field-closures/{field_id}', [FieldClosuresController::class, 'index']);
Route::get('/field-closures/{field_id}/{id}', [FieldClosuresController::class, 'show']);
Route::post('/field-closures', [FieldClosuresController::class, 'store']);

// Booking Controller
Route::post('/bookings', [BookingsController::class, 'store']);

// Payment Controller
Route::post('/cash-payment', [PaymentsController::class, 'storeCashPayment']);
