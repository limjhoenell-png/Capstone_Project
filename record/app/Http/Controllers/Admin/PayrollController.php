<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePayrollSettingsRequest;
use App\Models\AnnualBenefit;
use App\Models\EmployeePayrollDeduction;
use App\Models\PayrollPrintSetting;
use App\Models\PayrollSetting;
use App\Models\Payslip;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {}

    public function index(Request $request): View
    {
        $settings = PayrollSetting::current();
        $validated = $request->validate([
            'period_start' => ['sometimes', 'date_format:Y-m-d'],
            'period_end' => ['sometimes', 'date_format:Y-m-d'],
            'employee_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where(
                fn ($query) => $query->whereIn('role', User::personnelRoles())
            )],
        ]);

        $periodStart = $validated['period_start'] ?? Carbon::now()->startOfMonth()->toDateString();
        $periodEnd = $validated['period_end'] ?? Carbon::now()->endOfMonth()->toDateString();

        $from = Carbon::parse($periodStart)->startOfDay();
        $to = Carbon::parse($periodEnd)->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $employees = User::query()
            ->personnel()
            ->when(
                $validated['employee_id'] ?? null,
                fn ($query, $employeeId) => $query->whereKey((int) $employeeId)
            )
            ->with('payrollDeductions')
            ->orderBy('name')
            ->get();

        $lines = [];
        foreach ($employees as $employee) {
            $quote = $this->payrollService->quote($employee, $from, $to, $settings);

            $lines[] = [
                'employee' => $employee,
                'total_hours' => $quote['split']['total_hours'],
                'attendance_hours' => $quote['split']['attendance_hours'],
                'regular_hours' => $quote['split']['regular_hours'],
                'overtime_hours' => $quote['split']['overtime_hours'],
                'equivalent_days' => $quote['equivalent_days'],
                'regular_pay' => $quote['regular_pay'],
                'overtime_pay' => $quote['overtime_pay'],
                'gross_pay' => $quote['gross_pay'],
                'earning_total' => $quote['earning_total'],
                'earning_lines' => $quote['earning_lines'],
                'holiday_pay' => $quote['holiday_pay'],
                'rest_day_pay' => $quote['rest_day_pay'],
                'night_diff_pay' => $quote['night_diff_pay'],
                'undertime_hours' => $quote['undertime_hours'],
                'deductions_total' => $quote['deductions_total'],
                'deduction_lines' => $quote['deduction_lines'],
                'net_pay' => $quote['net_pay'],
                'daily_breakdown' => $quote['daily_breakdown'],
                'attendance_records' => $quote['split']['days'],
            ];
        }

        return view('admin.payroll.index', [
            'title' => 'Payroll',
            'settings' => $settings,
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'employee_id' => $validated['employee_id'] ?? null,
            'lines' => $lines,
            'employees' => $employees,
            'printSettings' => PayrollPrintSetting::current(),
        ]);
    }

    public function createAnnualBenefit(Request $request): View
    {
        $validated = $request->validate([
            'period_start' => ['sometimes', 'date_format:Y-m-d'],
            'period_end' => ['sometimes', 'date_format:Y-m-d'],
        ]);
        $periodStart = $validated['period_start'] ?? Carbon::now()->startOfMonth()->toDateString();
        $periodEnd = $validated['period_end'] ?? Carbon::now()->endOfMonth()->toDateString();
        $from = Carbon::parse($periodStart)->startOfDay();
        $to = Carbon::parse($periodEnd)->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $employees = User::query()
            ->personnel()
            ->orderBy('name')
            ->get();

        return view('admin.payroll.create-annual-benefits', [
            'title' => 'Create Annual Benefits',
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'employees' => $employees,
        ]);
    }

    public function printReport(Request $request): View
    {
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'source_fund' => ['nullable', 'string', 'max:120'],
        ]);
        $from = Carbon::createFromFormat('!Y-m', $data['period'])->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $printSettings = PayrollPrintSetting::current();
        $settings = PayrollSetting::current();
        $employees = User::query()
            ->personnel()
            ->with('payrollDeductions')
            ->orderBy('name')
            ->get();

        $lines = $employees->map(function (User $employee) use ($from, $to, $settings): array {
            $quote = $this->payrollService->quote($employee, $from, $to, $settings);

            return [
                'employee' => $employee,
                'gross_pay' => $quote['gross_pay'],
                'deductions_total' => $quote['deductions_total'],
                'net_pay' => $quote['net_pay'],
            ];
        });

        return view('admin.payroll.print', [
            'title' => 'Payroll Report',
            'printSettings' => $printSettings,
            'periodStart' => $from,
            'periodEnd' => $to,
            'payrollNumber' => str_ireplace(
                ['{year}', '{month}'],
                [$from->format('Y'), $from->format('m')],
                (string) $printSettings->payroll_number_format
            ),
            'sourceFund' => trim((string) ($data['source_fund'] ?? '')) !== ''
                ? trim((string) $data['source_fund'])
                : (string) $printSettings->source_fund,
            'preparedDateDescription' => str_replace(
                ['{date}', '{DATE}'],
                now()->format('jS \\d\\a\\y \\o\\f F Y'),
                (string) $printSettings->prepared_date_description
            ),
            'lines' => $lines,
            'grossTotal' => round($lines->sum('gross_pay'), 2),
            'deductionsTotal' => round($lines->sum('deductions_total'), 2),
            'netTotal' => round($lines->sum('net_pay'), 2),
        ]);
    }

    public function updatePrintLayout(Request $request): RedirectResponse
    {
        $fields = [
            'barangay_name' => ['required', 'string', 'max:120'],
            'city_name' => ['required', 'string', 'max:120'],
            'report_title' => ['required', 'string', 'max:120'],
            'source_fund' => ['nullable', 'string', 'max:120'],
            'payroll_number_format' => ['required', 'string', 'max:64'],
            'period_covered_label' => ['required', 'string', 'max:80'],
            'payroll_number_label' => ['required', 'string', 'max:80'],
            'source_fund_label' => ['required', 'string', 'max:80'],
            'date_prepared_label' => ['required', 'string', 'max:80'],
            'prepared_date_description' => ['required', 'string', 'max:180'],
            'punong_barangay_name' => ['nullable', 'string', 'max:120'],
            'punong_barangay_designation' => ['required', 'string', 'max:120'],
            'appropriations_chair_name' => ['nullable', 'string', 'max:120'],
            'appropriations_chair_designation' => ['required', 'string', 'max:160'],
            'treasurer_name' => ['nullable', 'string', 'max:120'],
            'treasurer_designation' => ['required', 'string', 'max:120'],
            'approved_by_name' => ['nullable', 'string', 'max:120'],
            'approved_by_designation' => ['required', 'string', 'max:120'],
            'prepared_by_name' => ['nullable', 'string', 'max:120'],
            'prepared_by_designation' => ['required', 'string', 'max:120'],
        ];
        $data = $request->validate($fields);
        $data = array_map(
            static fn (?string $value): string => strtoupper(trim((string) $value)),
            $data
        );

        PayrollPrintSetting::current()->update($data);

        return redirect()
            ->route('admin.payroll.index')
            ->with('status', 'Payroll print layout saved.');
    }

    public function breakdown(Request $request, User $employee): View
    {
        abort_unless($employee->isPersonnel(), 404);

        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $from = Carbon::parse($validated['period_start'])->startOfDay();
        $to = Carbon::parse($validated['period_end'])->endOfDay();
        $settings = PayrollSetting::current();
        $split = $this->payrollService->attendanceSplit($employee, $from, $to, $settings);

        return view('admin.payroll.breakdown', [
            'title' => 'Daily attendance breakdown',
            'employee' => $employee,
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'settings' => $settings,
            'days' => $split['days'],
            'total_hours' => $split['total_hours'],
            'regular_hours' => $split['regular_hours'],
        ]);
    }

    public function settings(Request $request): View
    {
        $selectedEmployeeId = $request->integer('employee');
        $settings = PayrollSetting::current();

        $employees = User::query()
            ->personnel()
            ->when($selectedEmployeeId, fn ($query) => $query->whereKey($selectedEmployeeId))
            ->with('payrollDeductions')
            ->orderBy('name')
            ->get();
        $activityItems = $this->payrollSettingsActivity($settings, $employees);
        $perPage = 5;
        $lastPage = max(1, (int) ceil($activityItems->count() / $perPage));
        $currentPage = min(max(1, $request->integer('activity_page', 1)), $lastPage);
        $paginatedActivityItems = new LengthAwarePaginator(
            $activityItems->forPage($currentPage, $perPage)->values(),
            $activityItems->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => 'activity_page',
            ]
        );

        return view('admin.payroll.settings', [
            'title' => 'Payroll settings',
            'settings' => $settings,
            'employees' => $employees,
            'selectedEmployeeId' => $selectedEmployeeId,
            'weekdays' => [
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
                7 => 'Sunday',
            ],
            'defaultRestDays' => array_map(
                fn (int $day): int => $day === 0 ? 7 : $day,
                $settings->restDayNumbers()
            ),
            'deductionPresets' => EmployeePayrollDeduction::commonDeductionNames(),
            'activityItems' => $paginatedActivityItems,
        ]);
    }

    public function updateSettings(UpdatePayrollSettingsRequest $request): RedirectResponse
    {
        $selectedEmployeeId = $request->integer('employee');
        $settings = PayrollSetting::current();
        $payload = [];
        foreach ([
            'overtime_multiplier',
            'shift_start',
            'shift_end',
            'grace_minutes',
            'late_penalty_increment_minutes',
            'late_penalty_amount',
            'rest_days',
            'rest_day_multiplier',
            'regular_holiday_multiplier',
            'special_holiday_multiplier',
        ] as $field) {
            if ($request->exists($field)) {
                $payload[$field] = $request->validated($field);
            }
        }
        if ($request->exists('apply_statutory')) {
            $payload['apply_statutory'] = $request->boolean('apply_statutory');
        }
        $settings->update($payload);

        return redirect()
            ->route('admin.payroll.settings', $selectedEmployeeId ? ['employee' => $selectedEmployeeId] : [])
            ->with('status', 'Payroll settings saved.');
    }

    public function updateEmployeeSettings(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'pay_rate_type' => ['required', Rule::in([User::PAY_RATE_DAILY, User::PAY_RATE_HOURLY])],
            'pay_rate' => ['nullable', 'numeric', 'min:0'],
            'hours_per_day' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:24'],
            'monthly_incentive' => ['required', 'numeric', 'min:0'],
            'working_days_present' => ['required', 'accepted'],
            'working_days' => ['sometimes', 'array'],
            'working_days.*' => ['integer', 'distinct', Rule::in(range(1, 7))],
            'employee_rest_days_present' => ['required', 'accepted'],
            'employee_rest_days' => ['sometimes', 'array'],
            'employee_rest_days.*' => ['integer', 'distinct', Rule::in(range(1, 7))],
        ]);

        if (($request->input('pay_rate') ?? null) === '') {
            $data['pay_rate'] = null;
        }

        $data['working_days'] = array_values(array_unique(array_map(
            'intval',
            $request->input('working_days', [])
        )));
        $data['employee_rest_days'] = array_values(array_unique(array_map(
            'intval',
            $request->input('employee_rest_days', [])
        )));
        unset($data['working_days_present'], $data['employee_rest_days_present']);

        $employee->update($data);

        return redirect()
            ->to(route('admin.payroll.settings', ['employee' => $employee->id]).'#employee-payroll-'.$employee->id)
            ->with('status', 'Employee payroll settings saved for '.$employee->name.'.');
    }

    public function storeAnnualBenefit(Request $request): RedirectResponse
    {
        if (!is_array($request->input('benefit_items'))) {
            $request->merge(['benefit_items' => [[
                'benefit_type' => $request->input('benefit_type'),
                'name' => $request->input('name'),
                'amount' => $request->input('amount'),
                'payment_date' => $request->input('payment_date'),
                'calculation_months' => $request->input('calculation_months'),
            ]]]);
        }

        $validator = Validator::make($request->all(), [
            'benefit_items' => ['required', 'array', 'min:1'],
            'benefit_items.*' => ['required', 'array'],
            'benefit_items.*.benefit_type' => ['required', Rule::in(['13Month', 'Other'])],
            'benefit_items.*.name' => ['nullable', 'string', 'max:120'],
            'benefit_items.*.amount' => ['nullable', 'numeric', 'gt:0', 'max:100000000'],
            'benefit_items.*.payment_date' => ['required', 'date_format:Y-m-d'],
            'benefit_items.*.calculation_months' => ['nullable', 'array', 'min:1'],
            'benefit_items.*.calculation_months.*' => ['required', 'integer', 'distinct', 'between:1,12'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')],
            'period_start' => ['sometimes', 'date_format:Y-m-d'],
            'period_end' => ['sometimes', 'date_format:Y-m-d'],
        ]);
        $validator->after(function ($validator) use ($request): void {
            foreach ((array) $request->input('benefit_items', []) as $index => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $type = $item['benefit_type'] ?? null;
                if ($type === 'Other') {
                    $name = $item['name'] ?? null;
                    if (!is_string($name) || trim($name) === '') {
                        $validator->errors()->add("benefit_items.{$index}.name", 'A benefit name is required.');
                    }
                    if (!isset($item['amount']) || $item['amount'] === '') {
                        $validator->errors()->add("benefit_items.{$index}.amount", 'An amount is required.');
                    }
                    if (!empty($item['calculation_months'])) {
                        $validator->errors()->add("benefit_items.{$index}.calculation_months", 'Calculation months are only available for 13th month benefits.');
                    }
                } elseif ($type === '13Month') {
                    if (filled($item['name'] ?? null)) {
                        $validator->errors()->add("benefit_items.{$index}.name", 'A name is not allowed for 13th month benefits.');
                    }
                    if (filled($item['amount'] ?? null)) {
                        $validator->errors()->add("benefit_items.{$index}.amount", 'An amount is calculated for 13th month benefits.');
                    }
                    if (empty($item['calculation_months'])) {
                        $validator->errors()->add("benefit_items.{$index}.calculation_months", 'Select at least one calculation month.');
                    }
                }
            }
        });
        $data = $validator->validate();
        $employeeIds = array_map('intval', $data['employee_ids']);
        $this->ensureEmployeeIdsAreEmployees($employeeIds);

        $preparedBenefits = [];
        foreach ($data['benefit_items'] as $item) {
            $isThirteenthMonth = $item['benefit_type'] === '13Month';
            $calculation = $isThirteenthMonth
                ? $this->calculateThirteenthMonthBenefits(
                    $employeeIds,
                    (int) Carbon::parse($item['payment_date'])->format('Y'),
                    array_map('intval', $item['calculation_months']),
                )
                : null;

            $preparedBenefits[] = [
                'data' => $item,
                'is_thirteenth_month' => $isThirteenthMonth,
                'calculation' => $calculation,
                'employee_payouts' => $calculation === null
                    ? null
                    : collect($calculation['employees'])->mapWithKeys(
                        fn (array $employee): array => [$employee['id'] => ['amount' => $employee['amount']]]
                    )->all(),
            ];
        }

        DB::transaction(function () use ($preparedBenefits, $employeeIds): void {
            foreach ($preparedBenefits as $prepared) {
                $item = $prepared['data'];
                $calculation = $prepared['calculation'];
                $benefit = AnnualBenefit::query()->create([
                    'benefit_type' => $item['benefit_type'],
                    'name' => $prepared['is_thirteenth_month'] ? '13Month' : $item['name'],
                    'amount' => $prepared['is_thirteenth_month'] ? $calculation['total_amount'] : $item['amount'],
                    'payment_date' => $item['payment_date'],
                    'calculation_year' => $prepared['is_thirteenth_month'] ? $calculation['year'] : null,
                    'selected_months' => $prepared['is_thirteenth_month'] ? $calculation['selected_months'] : null,
                ]);
                $benefit->employees()->sync($prepared['employee_payouts'] ?? $employeeIds);
            }
        });

        $thirteenthMonthCount = collect($preparedBenefits)->where('is_thirteenth_month', true)->count();
        $message = count($preparedBenefits).' annual benefit(s) saved and assigned to '.count($employeeIds).' employee(s).';
        if ($thirteenthMonthCount > 0) {
            $message .= ' 13th month benefits were calculated from saved payslips.';
        }

        return redirect()
            ->route('admin.payroll.index', array_filter([
                'period_start' => $data['period_start'] ?? null,
                'period_end' => $data['period_end'] ?? null,
            ]))
            ->with('status', $message);
    }

    public function previewAnnualBenefit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')],
            'calculation_months' => ['required', 'array', 'min:1'],
            'calculation_months.*' => ['required', 'integer', 'distinct', 'between:1,12'],
        ]);
        $employeeIds = array_map('intval', $data['employee_ids']);
        $this->ensureEmployeeIdsAreEmployees($employeeIds);

        return response()->json($this->calculateThirteenthMonthBenefits(
            $employeeIds,
            (int) Carbon::parse($data['payment_date'])->format('Y'),
            array_map('intval', $data['calculation_months']),
        ));
    }

    /**
     * @param  list<int>  $employeeIds
     */
    private function ensureEmployeeIdsAreEmployees(array $employeeIds): void
    {
        $employeeCount = User::query()
            ->personnel()
            ->whereKey($employeeIds)
            ->count();
        if ($employeeCount !== count($employeeIds)) {
            throw ValidationException::withMessages([
                'employee_ids' => 'Select only registered personnel.',
            ]);
        }
    }

    /**
     * @param  list<int>  $employeeIds
     * @param  list<int>  $selectedMonths
     * @return array{
     *     year:int,
     *     selected_months:list<int>,
     *     employees:list<array{id:int,name:string,months_worked:int,divisor:int,amount:float,monthly_pay:list<array{month:int,label:string,basic_pay:float,worked:bool}>}>,
     *     total_amount:float
     * }
     */
    private function calculateThirteenthMonthBenefits(array $employeeIds, int $year, array $selectedMonths): array
    {
        $selectedMonths = array_values(array_unique($selectedMonths));
        sort($selectedMonths);
        $selectedMonthLookup = array_fill_keys($selectedMonths, true);

        $employees = User::query()
            ->personnel()
            ->whereKey($employeeIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->keyBy('id');
        $monthlyPays = [];

        Payslip::query()
            ->whereIn('user_id', $employeeIds)
            ->where('kind', 'regular')
            ->whereBetween('period_start', [$year.'-01-01', $year.'-12-31'])
            ->get(['user_id', 'period_start', 'regular_pay'])
            ->each(function (Payslip $payslip) use (&$monthlyPays, $selectedMonthLookup): void {
                $month = (int) $payslip->period_start->format('n');
                if (! isset($selectedMonthLookup[$month])) {
                    return;
                }

                $employeeId = (int) $payslip->user_id;
                $monthlyPays[$employeeId][$month] = ($monthlyPays[$employeeId][$month] ?? 0.0)
                    + (float) $payslip->regular_pay;
            });

        $monthLabels = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
        $employeeAmounts = [];
        $totalAmount = 0.0;

        foreach ($employees as $employee) {
            $employeeMonthlyPays = $monthlyPays[$employee->id] ?? [];
            $workedMonths = count($employeeMonthlyPays);
            $divisor = min(12, $workedMonths);
            $sum = array_sum($employeeMonthlyPays);
            $amount = $divisor > 0 ? round($sum / $divisor, 2) : 0.0;
            $totalAmount += $amount;
            $monthlyPay = [];

            foreach ($selectedMonths as $month) {
                $monthlyPay[] = [
                    'month' => $month,
                    'label' => $monthLabels[$month],
                    'basic_pay' => round((float) ($employeeMonthlyPays[$month] ?? 0), 2),
                    'worked' => array_key_exists($month, $employeeMonthlyPays),
                ];
            }

            $employeeAmounts[] = [
                'id' => (int) $employee->id,
                'name' => (string) $employee->name,
                'months_worked' => $workedMonths,
                'divisor' => $divisor,
                'amount' => $amount,
                'monthly_pay' => $monthlyPay,
            ];
        }

        return [
            'year' => $year,
            'selected_months' => $selectedMonths,
            'employees' => $employeeAmounts,
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Lightweight settings activity feed without introducing new schema.
     *
     * @return Collection<int, array{title: string, subtitle: string, at: string}>
     */
    private function payrollSettingsActivity(PayrollSetting $settings, Collection $employees): Collection
    {
        $items = collect([
            [
                'title' => 'Global work-hours configuration updated',
                'subtitle' => 'Hours/day: '.number_format((float) $settings->hours_per_full_day, 2),
                'at' => (string) ($settings->updated_at ?? $settings->created_at),
            ],
        ]);

        foreach ($employees as $employee) {
            if ($employee->pay_rate !== null) {
                $items->push([
                    'title' => 'Employee compensation settings updated',
                    'subtitle' => $employee->name.' - '.strtoupper((string) $employee->pay_rate_type).' rate '.number_format((float) $employee->pay_rate, 2),
                    'at' => (string) ($employee->updated_at ?? $employee->created_at),
                ]);
            }

            foreach ($employee->payrollDeductions as $deduction) {
                $label = $deduction->calculation_type === EmployeePayrollDeduction::CALCULATION_PERCENT_GROSS
                    ? number_format((float) $deduction->value, 2).'% of gross'
                    : number_format((float) $deduction->value, 2).' fixed';

                $items->push([
                    'title' => 'Deduction rule configured',
                    'subtitle' => $employee->name.' - '.$deduction->name.' ('.$label.')',
                    'at' => (string) ($deduction->updated_at ?? $deduction->created_at),
                ]);
            }
        }

        return $items
            ->filter(fn (array $item): bool => $item['at'] !== '')
            ->sortByDesc('at')
            ->values();
    }
}
