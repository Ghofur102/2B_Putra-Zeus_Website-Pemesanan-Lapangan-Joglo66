<?php

namespace App\Http\Requests\Owner;

use App\Enums\UserRole;
use App\Enums\GeneralStatus;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isSystem = filter_var($this->is_system, FILTER_VALIDATE_BOOLEAN);
        $employeeId = $this->route('id');

        // Mencari ID user terkait untuk pengecualian aturan unik email
        $userId = DB::table('employees')->where('id', $employeeId)->value('fk_user_id');

        $rules = [
            'name'         => 'required|string|max:60',
            'phone_number' => 'nullable|string|max:20',
            'address'      => 'nullable|string',
            'position'     => 'required|string|max:50',
            'base_salary'  => 'required|integer|min:0',
            'status'       => ['required', new Enum(GeneralStatus::class)],
            'is_system'    => 'required|boolean'
        ];

        if ($isSystem) {
            $rules['email']    = 'required|email|unique:users,email,' . $userId;
            $rules['password'] = $userId ? 'nullable|string|min:8' : 'required|string|min:8';
            $rules['role']     = ['required', Rule::in([UserRole::WORKER->value, UserRole::TREASURER->value])];
        }

        return $rules;
    }
}
