<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'parent_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
