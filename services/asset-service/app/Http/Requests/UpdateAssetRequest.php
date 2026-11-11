<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'designation' => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => ['sometimes', 'required', 'integer', Rule::exists('categories', 'id')],
            'location_id' => ['sometimes', 'required', 'integer', Rule::exists('locations', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'acquisition_date' => ['sometimes', 'required', 'date'],
            'acquisition_value' => ['sometimes', 'required', 'numeric', 'min:0'],
            'useful_life_years' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            // 'status' et 'code' ne sont pas modifiables ici :
            // statut via POST /assets/{asset}/status, code immuable.
        ];
    }
}
