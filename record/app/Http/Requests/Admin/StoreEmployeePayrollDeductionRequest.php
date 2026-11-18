<?php

namespace App\Http\Requests\Admin;

use App\Models\EmployeePayrollDeduction;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeePayrollDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn('role', User::personnelRoles())
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'calculation_type' => [
                'required',
                'string',
                Rule::in([
                    EmployeePayrollDeduction::CALCULATION_FIXED,
                    EmployeePayrollDeduction::CALCULATION_PERCENT_GROSS,
                ]),
            ],
            'value' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('value', 'max:100', fn ($input) => ($input['calculation_type'] ?? '') === EmployeePayrollDeduction::CALCULATION_PERCENT_GROSS);
    }
}
