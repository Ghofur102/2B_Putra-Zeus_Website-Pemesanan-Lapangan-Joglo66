<?php

namespace App\Http\Requests\Tenant\Booking;

use Illuminate\Foundation\Http\FormRequest;

class FetchSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field_id' => ['required', 'integer', 'exists:mysql_joglo66_app.fields,id'],
            'date'     => ['required', 'date_format:Y-m-d'],
        ];
    }
}
