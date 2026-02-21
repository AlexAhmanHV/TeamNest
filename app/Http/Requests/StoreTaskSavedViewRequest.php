<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskSavedViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'assignee' => ['nullable', 'string'],
            'overdue' => ['nullable', 'in:0,1'],
            'q' => ['nullable', 'string', 'max:120'],
        ];
    }
}
