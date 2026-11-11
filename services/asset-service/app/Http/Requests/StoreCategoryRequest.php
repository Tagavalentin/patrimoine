<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // l'autorisation est déléguée à la Policy
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:categories,code'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
