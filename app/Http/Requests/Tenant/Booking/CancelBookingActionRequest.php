<?php

namespace App\Http\Requests\Tenant\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CancelBookingActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'detail_booking_id' => ['required', 'exists:mysql_joglo66_app.booking_details,id'],
            'reason'            => ['required', 'string', 'max:500'],
        ];
    }
}
