<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_ids' => 'required|array|min:1|max:100',
            'task_ids.*' => 'required|integer|exists:tasks,id',
            'status' => 'required|string|in:pending,in_progress,completed,cancelled',
        ];
    }
}
