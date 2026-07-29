<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role check is handled in BusinessService::updateForUser()
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => ['sometimes', 'string', 'max:255'],
            'type'            => ['sometimes', 'string', 'max:100'],
            'registration_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address'         => ['sometimes', 'nullable', 'string', 'max:500'],
            'phone'           => ['sometimes', 'nullable', 'string', 'max:20'],
            'email'           => ['sometimes', 'nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'   => 'Business name must not exceed 255 characters.',
            'email.email'=> 'Please provide a valid business email address.',
            'phone.max'  => 'Phone number must not exceed 20 characters.',
        ];
    }
}
