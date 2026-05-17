<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\Auth\AuthController;
use App\Http\Controllers\Tenant\Auth\RegisterController;
use App\Http\Controllers\Tenant\Auth\ForgotPasswordController;
use App\Http\Controllers\Tenant\Auth\ProfileController;
use App\Http\Controllers\Tenant\Booking\BookingController;
use App\Http\Controllers\Tenant\Booking\DashboardController;
use App\Http\Controllers\Tenant\Booking\ScheduleController;
use App\Http\Controllers\Tenant\Booking\RescheduleDetailBookingController;
use App\Http\Controllers\Tenant\Booking\CancelledDetailBookingController;
use App\Http\Controllers\Tenant\Payment\PaymentController;
use App\Http\Controllers\Tenant\Booking\HistoryController;
use App\Http\Middleware\CheckTenantRole;

Route::get('/', function () {
    return redirect()->route('tenant.booking.dashboard');
});

Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verification.verify');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/verify-notice', [AuthController::class, 'showVerificationNotice'])->name('verification.notice');
    Route::post('/verification/send', [AuthController::class, 'sendVerificationEmail'])->name('verification.send');

    Route::middleware(CheckTenantRole::class)->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('password.change');

        Route::prefix('tenant/booking')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('tenant.booking.dashboard');
            Route::get('/transactions', [HistoryController::class, 'index'])->name('tenant.booking.transaction');
            Route::get('/transactions/history/{id}', [HistoryController::class, 'show'])->name('tenant.booking.history.show');
            Route::get('/fetch-slots', [ScheduleController::class, 'fetchSlots'])->name('tenant.booking.fetch-slots');
            Route::get('/create-form', [BookingController::class, 'createForm'])->name('tenant.booking.create-form');
            Route::post('/confirm-form', [BookingController::class, 'confirmForm'])->name('tenant.booking.confirm-form');
            Route::post('/store', [BookingController::class, 'store'])->name('tenant.booking.store');
            Route::get('/success/{booking_id}', [BookingController::class, 'success'])->name('tenant.booking.success');
            // route reschedule detail booking
            Route::get('/form-reschedule/{detail_booking_id}', [RescheduleDetailBookingController::class, 'formInput'])->name('tenant.booking.form.reschedule');
            Route::post('/confirmation-reschedule', [RescheduleDetailBookingController::class, 'confirmation'])->name('tenant.booking.confirmation.reschedule');
            Route::post('/process-reschedule', [RescheduleDetailBookingController::class, 'process'])->name('tenant.booking.process.reschedule');
            // route cancelled detail booking
            Route::get('/form-cancelled/{detail_booking_id}', [CancelledDetailBookingController::class, 'formInput'])->name('tenant.booking.form.cancelled');
            Route::post('/confirmation-cancelled', [CancelledDetailBookingController::class, 'confirmation'])->name('tenant.booking.confirmation.cancelled');
            Route::post('/process-cancelled', [CancelledDetailBookingController::class, 'process'])->name('tenant.booking.process.cancelled');
        });
    });
});
