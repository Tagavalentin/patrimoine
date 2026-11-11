<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DisposalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // jwt.auth + policy 'create' dans le contrôleur
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'disposal_type' => ['required', Rule::in(DisposalType::values())],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'estimated_value' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'disposal_type.required' => 'Le type de sortie est obligatoire.',
            'disposal_type.in' => 'Type invalide : sale, donation, destruction ou written_off.',
            'reason.min' => 'Justifiez la sortie en au moins 10 caractères.',
        ];
    }
}
