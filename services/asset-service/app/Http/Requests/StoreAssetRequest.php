<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorisation déléguée à la Policy
    }

    public function rules(): array
    {
        return [
            'designation' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'acquisition_date' => ['required', 'date', 'before_or_equal:today'],
            'acquisition_value' => ['required', 'numeric', 'min:0'],
            'useful_life_years' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
        ];
    }
}
