<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'priority'    => ['sometimes', 'nullable', 'string', 'in:low,medium,high,urgent'],
            'due_date'    => ['sometimes', 'nullable', 'date'],
            'assigned_to' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
