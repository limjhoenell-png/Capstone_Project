<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeePayrollDeductionRequest;
use App\Http\Requests\Admin\UpdateEmployeePayrollDeductionRequest;
use App\Models\EmployeePayrollDeduction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeePayrollDeductionController extends Controller
{
    public function store(StoreEmployeePayrollDeductionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $selectedEmployeeId = (int) ($request->query('employee') ?: $validated['user_id']);
        $maxSort = (int) EmployeePayrollDeduction::query()
            ->where('user_id', $validated['user_id'])
            ->max('sort_order');

        EmployeePayrollDeduction::query()->create([
            'user_id' => $validated['user_id'],
            'name' => $validated['name'],
            'calculation_type' => $validated['calculation_type'],
            'value' => $validated['value'],
            'sort_order' => $maxSort + 1,
        ]);

        return redirect()
            ->to(route('admin.payroll.settings', ['employee' => $selectedEmployeeId]).'#employee-deductions-'.$selectedEmployeeId)
            ->with('status', 'Deduction added.');
    }

    public function update(UpdateEmployeePayrollDeductionRequest $request, EmployeePayrollDeduction $payrollDeduction): RedirectResponse
    {
        $selectedEmployeeId = (int) ($request->query('employee') ?: $payrollDeduction->user_id);
        $payrollDeduction->update($request->validated());

        return redirect()
            ->to(route('admin.payroll.settings', ['employee' => $selectedEmployeeId]).'#employee-deductions-'.$selectedEmployeeId)
            ->with('status', 'Deduction updated.');
    }

    public function destroy(Request $request, EmployeePayrollDeduction $payrollDeduction): RedirectResponse
    {
        $selectedEmployeeId = (int) ($request->query('employee') ?: $payrollDeduction->user_id);
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($payrollDeduction->user->isPersonnel(), 403);

        $payrollDeduction->delete();

        return redirect()
            ->to(route('admin.payroll.settings', ['employee' => $selectedEmployeeId]).'#employee-deductions-'.$selectedEmployeeId)
            ->with('status', 'Deduction removed.');
    }
}
