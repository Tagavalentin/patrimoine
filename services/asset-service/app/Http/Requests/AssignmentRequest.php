<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide une affectation ou un transfert (mêmes champs).
 */
class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorisation déléguée à la Policy
    }

    public function rules(): array
    {
        return [
            'department_id' => [
                'required', 'integer',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'responsible_user_id' => ['nullable', 'integer', 'min:1'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'responsible_email' => ['nullable', 'email', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
