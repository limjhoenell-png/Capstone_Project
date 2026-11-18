<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'can_access_logbook' => $this->boolean('can_access_logbook'),
        ]);

        if ($this->has('employee_id')) {
            $raw = $this->input('employee_id');
            $value = is_string($raw) ? trim($raw) : $raw;

            if ($value === '' || $value === null) {
                $this->merge(['employee_id' => null]);
            }

            if (is_string($value) && $value !== '') {
                $this->merge(['employee_id' => $value]);
            }
        }

        if (($this->input('pay_rate') === '' || $this->input('pay_rate') === null) && $this->has('pay_rate')) {
            $this->merge(['pay_rate' => null]);
        }

    }

    public function rules(): array
    {
        $userId = $this->route('employee')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'employment_start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'role' => ['required', 'in:'.User::ROLE_ADMIN.','.User::ROLE_EMPLOYEE],
            'employee_id' => ['nullable', 'string', 'max:64'],
            'can_access_logbook' => ['required', 'boolean'],
            'pay_rate_type' => ['nullable', 'in:'.User::PAY_RATE_DAILY.','.User::PAY_RATE_HOURLY],
            'pay_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
