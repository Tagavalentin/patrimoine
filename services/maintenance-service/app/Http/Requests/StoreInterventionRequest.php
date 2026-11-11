<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'technician_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:5', 'max:2000'],
            'cost' => ['sometimes', 'numeric', 'min:0', 'max:999999999'],
            'performed_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'description.min' => 'Décrivez l\'intervention effectuée.',
            'performed_at.required' => 'La date de l\'intervention est obligatoire.',
            'performed_at.before_or_equal' => 'La date de l\'intervention ne peut pas être dans le futur.',
            'cost.min' => 'Le coût ne peut pas être négatif.',
        ];
    }
}
