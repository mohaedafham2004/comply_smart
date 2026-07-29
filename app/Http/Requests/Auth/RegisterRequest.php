<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // User fields
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:mongodb.users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Business fields (optional — defaults created if omitted)
            'business_name'            => ['nullable', 'string', 'max:255'],
            'business_type'            => ['nullable', 'string', 'max:100'],
            'business_registration_no' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'            => 'An account with this email already exists.',
            'password.min'            => 'Password must be at least 8 characters.',
            'password.confirmed'      => 'Passwords do not match.',
            'email.email'             => 'Please provide a valid email address.',
        ];
    }
}
