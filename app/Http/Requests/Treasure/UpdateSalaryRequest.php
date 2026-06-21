<?php

namespace App\Http\Requests\Treasure;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'bulan'       => ['required', 'integer', 'min:1', 'max:12'],
            'tahun'       => ['required', 'integer', 'min:2000', 'max:' . date('Y')],
            'amount_paid' => ['required', 'integer', 'min:0'],
            'bonus'       => ['nullable', 'integer', 'min:0'],
            'deduction'   => ['nullable', 'integer', 'min:0'],
            'notes'       => ['nullable', 'string']
        ];
    }
}
