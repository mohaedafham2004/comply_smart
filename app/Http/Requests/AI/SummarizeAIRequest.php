<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class SummarizeAIRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_id' => ['nullable', 'string'],
            'text'        => ['nullable', 'string', 'max:15000'],
        ];
    }
}
