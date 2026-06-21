<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'nominal'  => 'required|integer|min:0',
            'date'     => 'required|date',
            'note'     => 'nullable|string',
            'image'    => 'nullable|image|max:2048'
        ];
    }
}
