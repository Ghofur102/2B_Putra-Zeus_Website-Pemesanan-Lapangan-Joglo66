<?php

namespace App\Http\Requests\Tenant\Booking;

use App\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTenantBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field_id'     => ['required', 'exists:mysql_joglo66_app.fields,id'],
            'team_name'    => ['required', 'string', 'max:50'],
            'notes'        => ['nullable', 'string'],
            'booking_data' => ['required', 'json'],
            'payment_type' => ['required', 'in:down payment,final payment'],
        ];
    }
}
