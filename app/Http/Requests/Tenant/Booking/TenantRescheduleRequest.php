<?php

namespace App\Http\Requests\Tenant\Booking;

use Illuminate\Foundation\Http\FormRequest;

class TenantRescheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'detail_booking_id'   => ['required', 'exists:mysql_joglo66_app.booking_details,id'],
            'new_play_date'       => ['required', 'date', 'after_or_equal:today'],
            'new_start_play_time' => ['required', 'date_format:H:i'],
            'new_end_play_time'   => ['required', 'date_format:H:i', 'after:new_start_play_time'],
            'reason'              => ['required', 'string', 'max:500'],
        ];
    }
}
