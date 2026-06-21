<?php

namespace App\Http\Requests\Owner;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isSystem = filter_var($this->is_system, FILTER_VALIDATE_BOOLEAN);

        $rules = [
            'name'         => 'required|string|max:60',
            'phone_number' => 'nullable|string|max:20',
            'address'      => 'nullable|string',
            'position'     => 'required|string|max:50',
            'base_salary'  => 'required|integer|min:0',
            'join_date'    => 'required|date',
            'is_system'    => 'required|boolean'
        ];

        if ($isSystem) {
            $rules['email']    = 'required|email|unique:users,email';
            $rules['password'] = 'required|string|min:8';
            $rules['role']     = ['required', Rule::in([UserRole::WORKER->value, UserRole::TREASURER->value])];
        }

        return $rules;
    }
}
