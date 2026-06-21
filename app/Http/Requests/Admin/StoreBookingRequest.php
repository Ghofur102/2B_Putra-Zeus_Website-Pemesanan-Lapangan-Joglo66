<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'                    => ['required', 'integer', 'exists:users,id'],
            'field_id'                   => ['required', 'integer', 'exists:fields,id'],
            'team_name'                  => ['required', 'string', 'max:50'],
            'booking_date'               => ['required', 'date'],
            'customer_phone'             => ['nullable', 'string', 'max:20'],
            'customer_email'             => ['nullable', 'email', 'max:100'],
            'notes'                      => ['nullable', 'string'],
            'details'                    => ['required', 'array', 'min:1'],
            'details.*.start_play_time'  => ['required', 'date_format:H:i'],
            'details.*.end_play_time'    => ['required', 'date_format:H:i'],
            'details.*.play_date'        => ['required', 'date'],
            'details.*.price'            => ['required', 'integer', 'min:0'],
        ];
    }
}
