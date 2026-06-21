<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason'              => ['required', 'string'],
            'status_refund'       => ['nullable', 'string'],
            'fk_field_closure_id' => ['nullable', 'integer'],
        ];
    }
}
