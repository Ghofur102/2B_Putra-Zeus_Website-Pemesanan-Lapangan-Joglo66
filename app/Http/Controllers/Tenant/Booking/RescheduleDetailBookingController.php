<?php

namespace App\Http\Controllers\Tenant\Booking;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RescheduleDetailBookingController extends Controller
{
    // untuk menampilkan formulir inputan reschedule ke views (views/tenant/booking/reschedule/input.blade.php), masukkan parameter dari detail booking untuk menampilkan data yang mau di reschedule
    public function formInput(int $detail_booking_id) {

    }

    // untuk menampilkan halaman konfirmasi reschedule berisi informasi dari detail booking dan inputan dari form reschedule sebelumnya
    public function confirmation(Request $request) {

    }

    // proses reschedule detail booking
    public function process(Request $request) {

    }
}
