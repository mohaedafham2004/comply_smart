<?php

namespace App\Http\Requests\Document;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership enforced in DocumentService
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'in:' . implode(',', Document::CATEGORIES)],
            'file'        => [
                'required',
                'file',
                'max:10240',                      // 10 MB
                'mimes:pdf,jpg,jpeg,png,tiff,webp', // Cloudinary + OCR supported types
            ],
            'expiry_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max'   => 'File must not exceed 10 MB.',
            'file.mimes' => 'Only PDF, JPG, PNG, TIFF, and WebP files are allowed.',
            'category.in'=> 'Category must be one of: ' . implode(', ', Document::CATEGORIES) . '.',
        ];
    }
}
