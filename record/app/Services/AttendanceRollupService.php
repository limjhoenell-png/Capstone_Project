<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Support\AttendanceTimeWindow;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AttendanceRollupService
{
    /**
     * Consolidated daily attendance sourced exclusively from attendances.
     *
     * @return list<array<string, mixed>>
     */
    public function forUser(User $user, CarbonInterface $from, CarbonInterface $to, ?PayrollSetting $settings = null): array
    {
        $settings ??= PayrollSetting::current();
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        $manual = $this->manualByDate($user, $fromDate, $toDate);
        $holidays = $this->holidaysByDate($fromDate, $toDate);
        $dates = collect(array_keys($manual))
            ->sort()
            ->values();

        $rows = [];
        foreach ($dates as $date) {
            $row = $this->mergeDay(
                $user,
                (string) $date,
                $manual[$date] ?? null,
                $holidays[$date] ?? null,
                $settings
            );
            if ($row !== null) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forEmployees(Collection $employees, CarbonInterface $from, CarbonInterface $to, ?PayrollSetting $settings = null): array
    {
        $rows = [];
        foreach ($employees as $employee) {
            if ($employee instanceof User) {
                $rows = array_merge($rows, $this->forUser($employee, $from, $to, $settings));
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>|null  $manual
     */
    private function mergeDay(
        User $user,
        string $date,
        ?array $manual,
        ?Holiday $holiday,
        PayrollSetting $settings,
    ): ?array {
        $timeIn = $manual['time_in'] ?? null;
        $timeOut = $manual['time_out'] ?? null;
        $validTimeIn = $timeIn !== null && AttendanceTimeWindow::allows($timeIn);
        $minutes = $validTimeIn ? $this->minutesBetween($timeIn, $timeOut) : 0;
        $hours = round($minutes / 60, 4);

        if ($hours <= 0 && $timeIn === null && $timeOut === null) {
            return null;
        }

        $day = Carbon::parse($date);
        $late = false;
        if ($validTimeIn) {
            $cutoff = $day->copy()->setTimeFromTimeString($this->clock((string) $settings->shift_start))
                ->addMinutes((int) $settings->grace_minutes);
            $late = $timeIn->greaterThan($cutoff);
        }

        $employeeRestDays = $user->employee_rest_days;
        $isRest = is_array($employeeRestDays)
            ? in_array($day->dayOfWeekIso, $employeeRestDays, true)
            : in_array($day->dayOfWeek, $settings->restDayNumbers(), true);

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'full_name' => $user->full_name,
            'department' => $user->departmentLabel(),
            'date' => $date,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'hours' => round($hours, 4),
            'minutes' => $minutes,
            'valid_time_in' => $validTimeIn,
            'source' => 'attendance',
            'remarks' => (string) ($manual['remarks'] ?? ''),
            'attendance_id' => $manual['id'] ?? null,
            'late' => $late,
            'holiday' => $holiday?->kind,
            'holiday_name' => $holiday?->name,
            'rest_day' => $isRest && $holiday === null,
        ];
    }

    /**
     * @return array<string, array{id: int, time_in: ?Carbon, time_out: ?Carbon, remarks: string}>
     */
    private function manualByDate(User $user, Carbon $from, Carbon $to): array
    {
        if (! Schema::hasTable('attendances')) {
            return [];
        }

        $rows = Attendance::query()
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $byDate = [];
        foreach ($rows as $row) {
            $key = $row->attendance_date?->format('Y-m-d');
            if ($key === null) {
                continue;
            }
            $byDate[$key] = [
                'id' => $row->id,
                'time_in' => $row->time_in ? Carbon::parse($row->time_in) : null,
                'time_out' => $row->time_out ? Carbon::parse($row->time_out) : null,
                'remarks' => (string) ($row->remarks ?? ''),
            ];
        }

        return $byDate;
    }

    /**
     * @return array<string, Holiday>
     */
    private function holidaysByDate(Carbon $from, Carbon $to): array
    {
        if (! Schema::hasTable('holidays')) {
            return [];
        }

        return Holiday::query()
            ->where('holiday_date', '>=', $from->toDateString())
            ->where('holiday_date', '<=', $to->copy()->endOfDay()->toDateTimeString())
            ->get()
            ->keyBy(fn (Holiday $holiday) => $holiday->holiday_date->format('Y-m-d'))
            ->all();
    }

    private function minutesBetween(mixed $in, mixed $out): int
    {
        if ($in === null || $out === null) {
            return 0;
        }

        $start = Carbon::parse($in);
        $end = Carbon::parse($out);
        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->copy()->addDay();
        }

        return min((int) $start->diffInMinutes($end), AttendanceTimeWindow::MAX_PAYABLE_MINUTES);
    }

    private function clock(string $value): string
    {
        $value = trim($value);
        if (strlen($value) === 5) {
            return $value.':00';
        }

        return $value !== '' ? $value : '00:00:00';
    }
}
