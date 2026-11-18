<?php

namespace App\Services;

use App\Models\AnnualBenefit;
use App\Models\Attendance;
use App\Models\EmployeePayrollDeduction;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Support\AttendanceTimeWindow;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class PayrollService
{
    public function __construct(
        private AttendanceRollupService $rollup,
        private StatutoryContributionService $statutory,
    ) {}

    /**
     * Hours between time in and time out for one attendance row (0 if incomplete).
     */
    public function workedHoursForRow(Attendance $attendance): float
    {
        $in = $attendance->time_in;
        $out = $attendance->time_out;

        if ($in === null || $out === null || ! AttendanceTimeWindow::allows($in)) {
            return 0.0;
        }

        $start = Carbon::parse($in);
        $end = Carbon::parse($out);

        if ($end->lessThan($start)) {
            $end = $end->copy()->addDay();
        }

        $minutes = (int) $start->diffInMinutes($end);

        return round(min($minutes, AttendanceTimeWindow::MAX_PAYABLE_MINUTES) / 60, 4);
    }

    /**
     * Total worked hours in [from, to] inclusive for attendances linked to this user.
     *
     * @return array{hours: float, rows: list<array{date: string, hours: float}>}
     */
    public function summarizePeriod(User $user, CarbonInterface $from, CarbonInterface $to): array
    {
        $daily = [];
        $totalHours = 0.0;

        foreach ($this->rollup->forUser($user, $from, $to) as $row) {
            $hours = (float) $row['hours'];
            if ($hours <= 0) {
                continue;
            }
            $daily[] = [
                'date' => (string) $row['date'],
                'hours' => round($hours, 4),
            ];
            $totalHours += $hours;
        }

        return [
            'hours' => round($totalHours, 4),
            'rows' => $daily,
        ];
    }

    public function equivalentDays(float $workedHours, PayrollSetting $settings, ?User $employee = null): float
    {
        $employeeDayBasis = $employee?->pay_rate_type === User::PAY_RATE_DAILY
            ? (float) ($employee->hours_per_day ?? 0)
            : 0.0;
        $perDay = $employeeDayBasis > 0
            ? $employeeDayBasis
            : (float) $settings->hours_per_full_day;
        if ($perDay <= 0) {
            $perDay = AttendanceTimeWindow::MAX_PAYABLE_HOURS;
        }

        return round($workedHours / $perDay, 4);
    }

    /**
     * Treat all recorded attendance hours as regular hours.
     *
     * @return array{
     *     total_hours: float,
     *     attendance_hours: float,
     *     regular_hours: float,
     *     regular_minutes: int,
     *     overtime_hours: float,
     *     days_worked: int,
     *     daily_breakdown: list<array{date: string, hours: float}>,
     *     days: list<array<string, mixed>>
     * }
     */
    public function attendanceSplit(User $user, CarbonInterface $from, CarbonInterface $to, PayrollSetting $settings): array
    {
        $days = $this->rollup->forUser($user, $from, $to, $settings);
        $totalMinutes = 0;
        $attendanceMinutes = 0;
        $billableMinutes = 0;
        $dailyLimit = $this->dailyHoursLimit($settings, $user);
        $dailyLimitMinutes = (int) round($dailyLimit * 60);
        $daily = [];
        $daysWorked = 0;
        foreach ($days as $index => $row) {
            $minutes = (int) ($row['minutes'] ?? round((float) $row['hours'] * 60));
            if ($minutes <= 0) {
                continue;
            }
            $payableMinutes = min($minutes, $dailyLimitMinutes);
            $days[$index]['payable_hours'] = round($payableMinutes / 60, 4);
            $totalMinutes += $minutes;
            $billableMinutes += $payableMinutes;
            if (($row['source'] ?? null) === 'attendance'
                && ($row['time_in'] ?? null) !== null
                && ($row['time_out'] ?? null) !== null) {
                $attendanceMinutes += $minutes;
            }
            $daysWorked++;
            $daily[] = ['date' => (string) $row['date'], 'hours' => round($payableMinutes / 60, 4)];
        }

        return [
            'total_hours' => round($totalMinutes / 60, 4),
            'attendance_hours' => round($attendanceMinutes / 60, 4),
            'regular_hours' => round($billableMinutes / 60, 4),
            'regular_minutes' => $billableMinutes,
            'overtime_hours' => 0.0,
            'days_worked' => $daysWorked,
            'daily_breakdown' => $daily,
            'days' => $days,
        ];
    }

    /**
     * Full pay quote for a period, including premiums, statutory deductions, and a zero net floor.
     *
     * @return array<string, mixed>
     */
    public function quote(User $user, CarbonInterface $from, CarbonInterface $to, ?PayrollSetting $settings = null): array
    {
        $settings ??= PayrollSetting::current();
        $split = $this->attendanceSplit($user, $from, $to, $settings);
        $amounts = $this->computePayAmounts($user, $split['regular_hours'], $settings, $split['regular_minutes']);
        $premiums = $this->premiums($user, $split['days'], $settings);

        $earningLines = $this->annualBenefitEarnings($user, $from, $to);
        $earningTotal = round(array_sum(array_column($earningLines, 'amount')), 2);
        $gross = round($amounts['gross_pay'] + $premiums['holiday_pay'] + $premiums['rest_day_pay'] + $earningTotal, 2);
        $skipStatutoryNames = (bool) $settings->apply_statutory;
        $custom = $this->computeDeductions($user, $gross, $skipStatutoryNames);
        $latePenalty = $this->latePenalty($split['days'], $settings);
        $statutory = $skipStatutoryNames
            ? $this->statutory->forGross($gross, Carbon::parse($from)->toDateString(), Carbon::parse($to)->toDateString())
            : ['total' => 0.0, 'items' => []];

        $latePenaltyItems = $latePenalty > 0
            ? [['name' => 'Late Attendance Penalty', 'calculation_type' => 'fixed', 'value' => $latePenalty, 'amount' => $latePenalty]]
            : [];
        $items = array_merge($custom['items'], $latePenaltyItems, $statutory['items']);
        $deductionsTotal = round($custom['total'] + $latePenalty + $statutory['total'], 2);
        $net = max(0, round($gross - $deductionsTotal, 2));

        return [
            'split' => $split,
            'regular_pay' => $amounts['regular_pay'],
            'overtime_pay' => $amounts['overtime_pay'],
            'gross_pay' => $gross,
            'earning_total' => $earningTotal,
            'earning_lines' => $earningLines,
            'equivalent_days' => $amounts['equivalent_days'],
            'night_diff_hours' => 0.0,
            'night_diff_pay' => 0.0,
            'holiday_pay' => $premiums['holiday_pay'],
            'rest_day_pay' => $premiums['rest_day_pay'],
            'undertime_hours' => $premiums['undertime_hours'],
            'deductions_total' => $deductionsTotal,
            'late_penalty_total' => $latePenalty,
            'deduction_lines' => $items,
            'net_pay' => $net,
            'daily_breakdown' => $split['daily_breakdown'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $days
     * @return array{holiday_pay: float, rest_day_pay: float, undertime_hours: float}
     */
    private function premiums(User $user, array $days, PayrollSetting $settings): array
    {
        $perDay = $this->dailyHoursLimit($settings, $user);

        $rate = (float) ($user->pay_rate ?? 0);
        $holidayPay = 0.0;
        $restPay = 0.0;
        $undertime = 0.0;

        foreach ($days as $day) {
            $minutes = (int) ($day['minutes'] ?? round((float) ($day['hours'] ?? 0) * 60));
            $worked = $minutes > 0;
            $perDayMinutes = (int) round($perDay * 60);
            if ($worked && $minutes < $perDayMinutes) {
                $undertime += ($perDayMinutes - $minutes) / 60;
            }

            $dayRegularMinutes = min($minutes, $perDayMinutes);
            $dayRegularPay = $user->pay_rate_type === User::PAY_RATE_HOURLY
                ? ($dayRegularMinutes / 60) * $rate
                : ($dayRegularMinutes / AttendanceTimeWindow::MAX_PAYABLE_MINUTES) * $rate;

            if ($worked && ! empty($day['holiday'])) {
                $multiplier = $day['holiday'] === 'special'
                    ? (float) $settings->special_holiday_multiplier
                    : (float) $settings->regular_holiday_multiplier;
                $holidayPay += $dayRegularPay * max(0, $multiplier - 1);
            } elseif ($worked && ! empty($day['rest_day'])) {
                $multiplier = (float) $settings->rest_day_multiplier;
                $restPay += $dayRegularPay * max(0, $multiplier - 1);
            }

        }

        return [
            'holiday_pay' => round($holidayPay, 2),
            'rest_day_pay' => round($restPay, 2),
            'undertime_hours' => round($undertime, 4),
        ];
    }

    /**
     * Regular pay and equivalent days from all recorded hours.
     *
     * @return array{
     *     regular_pay: float,
     *     overtime_pay: float,
     *     gross_pay: float,
     *     equivalent_days: float
     * }
     */
    public function computePayAmounts(
        User $user,
        float $regularHours,
        PayrollSetting $settings,
        ?int $regularMinutes = null,
    ): array
    {
        $regularMinutes ??= (int) round($regularHours * 60);
        $rate = $user->pay_rate;
        if ($rate === null) {
            return [
                'regular_pay' => 0.0,
                'overtime_pay' => 0.0,
                'gross_pay' => 0.0,
                'equivalent_days' => 0.0,
            ];
        }

        $rateAmount = (float) $rate;
        $equivRegular = $this->equivalentDays($regularMinutes / 60, $settings, $user);

        if ($user->pay_rate_type === User::PAY_RATE_HOURLY) {
            $regularPay = round(($regularMinutes / 60) * $rateAmount, 2);
        } else {
            $regularPay = round(($regularMinutes / AttendanceTimeWindow::MAX_PAYABLE_MINUTES) * $rateAmount, 2);
        }

        $gross = $regularPay;

        return [
            'regular_pay' => $regularPay,
            'overtime_pay' => 0.0,
            'gross_pay' => $gross,
            'equivalent_days' => $equivRegular,
        ];
    }

    private function dailyHoursLimit(PayrollSetting $settings, User $employee): float
    {
        if ($employee->pay_rate_type !== User::PAY_RATE_HOURLY) {
            return AttendanceTimeWindow::MAX_PAYABLE_HOURS;
        }

        $employeeHours = (float) ($employee->hours_per_day ?? 0);
        $hoursPerDay = $employeeHours > 0
            ? $employeeHours
            : (float) $settings->hours_per_full_day;

        return $hoursPerDay > 0
            ? min($hoursPerDay, AttendanceTimeWindow::MAX_PAYABLE_HOURS)
            : AttendanceTimeWindow::MAX_PAYABLE_HOURS;
    }

    /**
     * Apply persisted per-employee deduction rules against gross pay for the period.
     *
     * @return array{total: float, items: list<array{name: string, calculation_type: string, value: float, amount: float}>}
     */
    public function computeDeductions(User $user, float $grossPay, bool $skipStatutoryNames = false): array
    {
        if (! Schema::hasTable('employee_payroll_deductions')) {
            return ['total' => 0.0, 'items' => []];
        }

        $deductions = $user->relationLoaded('payrollDeductions')
            ? $user->payrollDeductions
            : $user->payrollDeductions()->get();

        if ($skipStatutoryNames) {
            $deductions = $deductions->reject(
                fn (EmployeePayrollDeduction $deduction): bool => $this->statutory->isStatutoryName($deduction->name)
            );
        }

        $items = [];
        $total = 0.0;

        foreach ($deductions as $d) {
            if ($d->calculation_type === EmployeePayrollDeduction::CALCULATION_PERCENT_GROSS) {
                $amount = round($grossPay * ((float) $d->value / 100), 2);
            } else {
                $amount = round((float) $d->value, 2);
            }
            $total += $amount;
            $items[] = [
                'name' => $d->name,
                'calculation_type' => $d->calculation_type,
                'value' => (float) $d->value,
                'amount' => $amount,
            ];
        }

        return [
            'total' => round($total, 2),
            'items' => $items,
        ];
    }

    /**
     * Charge each started late increment after the configured grace period.
     *
     * @param  list<array<string, mixed>>  $days
     */
    private function latePenalty(array $days, PayrollSetting $settings): float
    {
        $incrementMinutes = (int) ($settings->late_penalty_increment_minutes ?? 0);
        $amount = (float) ($settings->late_penalty_amount ?? 0);
        if ($incrementMinutes <= 0 || $amount <= 0) {
            return 0.0;
        }

        $graceSeconds = (int) ($settings->grace_minutes ?? 0) * 60;
        $incrementSeconds = $incrementMinutes * 60;
        $shiftStartValue = trim((string) ($settings->shift_start ?? '08:00:00'));
        if (strlen($shiftStartValue) === 5) {
            $shiftStartValue .= ':00';
        }

        $penaltyTotal = 0.0;
        foreach ($days as $day) {
            if (($day['source'] ?? null) !== 'attendance'
                || empty($day['time_in'])
                || empty($day['valid_time_in'])) {
                continue;
            }

            $shiftStart = Carbon::parse((string) $day['date'])
                ->setTimeFromTimeString($shiftStartValue);
            $timeIn = Carbon::parse($day['time_in']);
            if ($timeIn->lessThanOrEqualTo($shiftStart)) {
                continue;
            }

            $secondsLate = $shiftStart->diffInSeconds($timeIn);
            $secondsBeyondGrace = $secondsLate - $graceSeconds;
            if ($secondsBeyondGrace <= 0) {
                continue;
            }

            $increments = (int) ceil($secondsBeyondGrace / $incrementSeconds);
            $penaltyTotal += $increments * $amount;
        }

        return round($penaltyTotal, 2);
    }

    /**
     * Include assigned annual benefits paid within the selected payroll period.
     *
     * @return list<array{name: string, amount: float, payment_date: string}>
     */
    private function annualBenefitEarnings(User $user, CarbonInterface $from, CarbonInterface $to): array
    {
        if (! Schema::hasTable('annual_benefits') || ! Schema::hasTable('annual_benefit_user')) {
            return [];
        }

        return $user->annualBenefits()
            ->whereBetween('annual_benefits.payment_date', [
                Carbon::parse($from)->toDateString(),
                Carbon::parse($to)->toDateString(),
            ])
            ->orderBy('annual_benefits.payment_date')
            ->orderBy('annual_benefits.id')
            ->get()
            ->map(fn (AnnualBenefit $benefit): array => [
                'name' => $benefit->name,
                'amount' => (float) ($benefit->pivot?->amount ?? $benefit->amount),
                'payment_date' => $benefit->payment_date->toDateString(),
            ])
            ->all();
    }

    /**
     * @return list<array{name: string, amount: float}>
     */
    public function deductionItemsForPayslip(array $computeItems): array
    {
        $out = [];
        foreach ($computeItems as $row) {
            $out[] = [
                'name' => $row['name'],
                'amount' => $row['amount'],
            ];
        }

        return $out;
    }

    public function employeeDisplayName(User $user): string
    {
        $full = $user->getAttribute('full_name');
        if (is_string($full) && trim($full) !== '') {
            return trim($full);
        }

        return (string) $user->name;
    }

    public function employeeIdentifier(User $user): ?string
    {
        $id = $user->employee_id;

        if ($id === null || $id === '') {
            return null;
        }

        return (string) $id;
    }
}
