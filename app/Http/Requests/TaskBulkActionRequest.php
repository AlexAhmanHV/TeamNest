<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'min:1'],
            'action' => ['required', Rule::in(['status', 'priority', 'assign', 'delete'])],
            'status' => ['nullable', Rule::in(['todo', 'doing', 'done'])],
            'priority' => ['nullable', Rule::in(['low', 'med', 'high'])],
            'assigned_to_user_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
