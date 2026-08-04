<?php

namespace App\Http\Requests\Renewal;

use Illuminate\Foundation\Http\FormRequest;

class CreateRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'renewal_type' => ['required', 'string', 'max:100'],
            'due_date'     => ['required', 'date'],
            'document_id'  => ['nullable', 'string'],
            'notes'        => ['nullable', 'string'],
        ];
    }
}
