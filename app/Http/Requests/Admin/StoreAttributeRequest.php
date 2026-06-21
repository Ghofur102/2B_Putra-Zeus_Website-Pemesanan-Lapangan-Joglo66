<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fk_field_id' => 'required|exists:fields,id',
            'name'        => 'required|string|max:100',
            'type'        => 'required|string|max:50',
            'stock'       => 'required|integer|min:0',
            'price_hour'  => 'required|integer|min:0',
        ];
    }
}
