<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // jwt.auth déjà appliqué
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high', 'urgent'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'asset_id.required' => 'Le bien concerné est obligatoire.',
            'asset_id.min' => 'Le bien concerné est obligatoire.',
            'description.min' => 'Décrivez le problème en au moins 10 caractères.',
        ];
    }
}
