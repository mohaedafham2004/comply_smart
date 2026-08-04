<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class ChecklistAIRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_type' => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:500'],
            'location'      => ['nullable', 'string', 'max:100'],
        ];
    }
}
