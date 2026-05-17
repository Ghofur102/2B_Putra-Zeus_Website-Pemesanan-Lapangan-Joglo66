<?php

namespace App\Http\Controllers\Tenant\Booking;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CancelledDetailBookingController extends Controller
{
    // untuk menampilkan formulir inputan cancel ke views (views/tenant/booking/cancelled/input.blade.php), masukkan parameter dari detail booking untuk menampilkan data yang mau di cancel
    public function formInput(int $detail_booking_id) {

    }

    // untuk menampilkan halaman konfirmasi cancel berisi informasi dari detail booking dan inputan dari form cancel sebelumnya
    public function confirmation(Request $request) {

    }

    // proses meng-cancel detail booking
    public function process(Request $request) {

    }
}
