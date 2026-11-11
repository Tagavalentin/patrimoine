<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Category $category */
        $category = $this->route('category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('categories', 'code')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
