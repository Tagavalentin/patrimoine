<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CheckStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'check_status' => ['required', Rule::in(CheckStatus::values())],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'check_status.required' => 'Le résultat du contrôle est obligatoire.',
            'check_status.in' => 'Statut invalide : found, missing, damaged ou pending.',
        ];
    }
}
