<?php

namespace App\Http\Requests\Tenant\Payment;

use Illuminate\Foundation\Http\FormRequest;

class DuitkuCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchantOrderId' => ['required', 'string'],
            'reference'       => ['required', 'string'],
            'signature'       => ['required', 'string'],
            'amount'          => ['required', 'numeric'],
            'resultCode'      => ['required', 'string'],
            'paymentCode'     => ['nullable', 'string'],
        ];
    }
}
