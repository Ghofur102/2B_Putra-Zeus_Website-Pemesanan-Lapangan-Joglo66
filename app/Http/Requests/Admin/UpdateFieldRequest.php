<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'            => 'required|exists:fields,id',
            'name'          => 'sometimes|string|max:255',
            'description'   => 'sometimes|string',
            'category'      => 'sometimes|string|max:100',
            'image'         => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'pricing_rules' => 'sometimes',
        ];
    }
}
