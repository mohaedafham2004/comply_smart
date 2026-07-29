<?php

namespace App\Http\Requests\Document;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership enforced in DocumentService
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'category'    => ['sometimes', 'string', 'in:' . implode(',', Document::CATEGORIES)],
            'expiry_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
