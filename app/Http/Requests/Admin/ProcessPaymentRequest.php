<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id'        => ['required', 'integer', 'exists:bookings,id'],
            'booking_detail_id' => ['nullable', 'integer', 'exists:booking_details,id'],
            'payment_type'      => ['required', new Enum(PaymentType::class)],
            'method'            => ['required', 'in:cash'],
            'amount'            => ['required', 'integer', 'min:1'],
            'reference_id'      => ['nullable', 'string', 'max:255'],
        ];
    }
}
