<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                'in:' . implode(',', [
                    Task::STATUS_PENDING,
                    Task::STATUS_IN_PROGRESS,
                    Task::STATUS_COMPLETED,
                    Task::STATUS_OVERDUE,
                ]),
            ],
        ];
    }
}
