<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // jwt.auth déjà appliqué
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:5', 'max:255'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'department_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'location_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la campagne est obligatoire.',
            'name.min' => 'Le nom de la campagne doit contenir au moins 5 caractères.',
        ];
    }
}
