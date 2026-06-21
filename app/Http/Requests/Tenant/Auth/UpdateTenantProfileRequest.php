<?php

namespace App\Http\Requests\Tenant\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateTenantProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = Auth::guard('web')->user();

        return [
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(\+62|0)[0-9]{9,12}$/', 'unique:users,phone,' . $user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Nama harus diisi',
            'name.max'       => 'Nama maksimal 255 karakter',
            'phone.required' => 'Nomor HP harus diisi',
            'phone.regex'    => 'Format nomor HP tidak valid',
            'phone.unique'   => 'Nomor HP sudah digunakan oleh user lain',
            'email.required' => 'Email harus diisi',
            'email.email'    => 'Format email tidak valid',
            'email.unique'   => 'Email sudah digunakan oleh user lain',
        ];
    }
}
