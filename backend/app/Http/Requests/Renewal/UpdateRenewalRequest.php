<?php

namespace App\Http\Requests\Renewal;

use App\Models\Renewal;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'string', 'max:255'],
            'renewal_type' => ['sometimes', 'string', 'max:100'],
            'due_date'     => ['sometimes', 'date'],
            'status'       => [
                'sometimes',
                'string',
                'in:' . implode(',', [
                    Renewal::STATUS_UPCOMING,
                    Renewal::STATUS_DUE,
                    Renewal::STATUS_OVERDUE,
                    Renewal::STATUS_COMPLETED,
                ]),
            ],
            'document_id'  => ['sometimes', 'nullable', 'string'],
            'notes'        => ['sometimes', 'nullable', 'string'],
        ];
    }
}
