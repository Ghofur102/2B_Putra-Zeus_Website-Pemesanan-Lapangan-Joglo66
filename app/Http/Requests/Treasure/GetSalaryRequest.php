<?php

namespace App\Http\Requests\Treasure;

use Illuminate\Foundation\Http\FormRequest;

class GetSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . date('Y')],
        ];
    }
}
