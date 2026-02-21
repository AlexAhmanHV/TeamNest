<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'default_invite_role' => ['required', Rule::in(['admin', 'member'])],
            'timezone' => ['required', 'timezone'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'retention_days' => ['nullable', 'integer', 'min:7', 'max:3650'],
        ];
    }
}
