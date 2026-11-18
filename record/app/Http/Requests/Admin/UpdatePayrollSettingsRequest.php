<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'overtime_multiplier' => ['sometimes', 'numeric', 'min:1', 'max:5'],
            'shift_start' => ['sometimes', 'date_format:H:i'],
            'shift_end' => ['sometimes', 'date_format:H:i'],
            'grace_minutes' => ['sometimes', 'integer', 'min:0', 'max:180'],
            'late_penalty_increment_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'late_penalty_amount' => ['sometimes', 'numeric', 'min:0', 'max:1000000'],
            'rest_days' => ['sometimes', 'string', 'max:32'],
            'rest_day_multiplier' => ['sometimes', 'numeric', 'min:1', 'max:5'],
            'regular_holiday_multiplier' => ['sometimes', 'numeric', 'min:1', 'max:5'],
            'special_holiday_multiplier' => ['sometimes', 'numeric', 'min:1', 'max:5'],
            'apply_statutory' => ['sometimes', 'boolean'],
        ];
    }
}
