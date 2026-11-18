<?php

namespace App\Http\Requests\Admin;

use App\Models\EmployeePayrollDeduction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeePayrollDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()?->isAdmin()) {
            return false;
        }

        $deduction = $this->route('payrollDeduction');

        return $deduction instanceof EmployeePayrollDeduction
            && $deduction->user->isPersonnel();
    }

    public function rules(): array
    {
        return [
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
