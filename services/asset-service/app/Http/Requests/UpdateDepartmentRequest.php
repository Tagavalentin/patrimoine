<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Department $department */
        $department = $this->route('department');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('departments', 'code')->ignore($department->id)],
            // Un département ne peut pas être son propre parent
            'parent_id' => ['nullable', 'integer', Rule::exists('departments', 'id'), Rule::notIn([$department->id])],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
