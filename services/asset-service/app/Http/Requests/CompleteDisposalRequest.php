<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'disposal_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'sale_value' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'disposal_date.before_or_equal' => 'La date de sortie ne peut pas être dans le futur.',
            'sale_value.min' => 'Le prix de cession ne peut pas être négatif.',
        ];
    }
}
