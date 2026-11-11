<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'technician_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'scheduled_date.required' => 'La date planifiée est obligatoire.',
            'scheduled_date.after_or_equal' => 'La date planifiée doit être aujourd\'hui ou ultérieure.',
        ];
    }
}
