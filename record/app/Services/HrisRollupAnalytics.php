<?php

namespace App\Services;

use App\Models\PayrollSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HrisRollupAnalytics
{
    public function __construct(
        private AttendanceRollupService $rollup,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(int $employeeCount, string $reportPeriod): array
    {
        $settings = PayrollSetting::current();
        [$from, $to] = $this->windowFor($reportPeriod);
        $employees = User::query()->where('role', User::ROLE_EMPLOYEE)->orderBy('name')->get();
        $rows = collect($this->rollup->forEmployees($employees, $from, $to, $settings));

        $today = now()->toDateString();
        $todayRows = $this->rollup->forEmployees($employees, now()->startOfDay(), now()->endOfDay(), $settings);
        $presentToday = collect($todayRows)
            ->filter(fn (array $row): bool => (float) $row['hours'] > 0 || $row['time_in'] !== null)
            ->pluck('user_id')
            ->unique()
            ->count();

        $presentSlots = $rows->filter(fn (array $row): bool => (float) $row['hours'] > 0)->count();
        $expected = max(1, $employees->sum(
            fn (User $employee): int => $this->workingDayCount($from, $to, $employee, $settings)
        ));
        $withTimeIn = $rows->filter(fn (array $row): bool => $row['time_in'] !== null)->count();
        $lateCount = $rows->filter(fn (array $row): bool => (bool) $row['late'])->count();

        $daily = $rows
            ->groupBy('date')
            ->map(fn ($group, $day): array => ['day' => (string) $day, 'count' => $group->count()])
            ->sortKeys()
            ->values()
            ->all();

        return [
            'summary' => [
                'total_employees' => $employeeCount,
                'present_today' => $presentToday,
                'absent_today' => max(0, $employeeCount - $presentToday),
                'attendance_rate_pct' => round(min(100, ($presentSlots / $expected) * 100), 1),
                'late_rate_pct' => $withTimeIn > 0 ? round($lateCount / $withTimeIn * 100, 1) : 0.0,
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
            ],
            'leaders' => [
                'most_present' => $this->leader($rows, $employees, 'present', $from, $to, $settings),
                'most_absent' => $this->leader($rows, $employees, 'absent', $from, $to, $settings),
                'most_late' => $this->leader($rows, $employees, 'late', $from, $to, $settings),
                'highest_overtime' => $this->highestOvertime(),
            ],
            'overtime_trend' => $this->overtimeTrend(),
            'attendance_series' => $this->series($daily),
            'department' => $this->departments($employees, $rows),
            'heatmap' => $this->heatmap($rows, $from),
            'top_performers' => $this->topPerformers($rows),
            'recent_activity' => $this->recent($settings),
            'status_monitor' => $this->statusMonitor($settings),
            'time_in_trend' => $this->timeInTrend($rows),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function windowFor(string $period): array
    {
        return match ($period) {
            'day' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
            'week' => [now()->subWeeks(12)->startOfWeek(), now()->endOfDay()],
            'year' => [now()->subYears(4)->startOfYear(), now()->endOfDay()],
            default => [now()->subMonths(12)->startOfMonth(), now()->endOfDay()],
        };
    }

    private function workingDayCount(Carbon $from, Carbon $to, User $employee, PayrollSetting $settings): int
    {
        $workingDays = $employee->working_days;
        $restDays = $employee->employee_rest_days;
        $count = 0;
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $isRestDay = is_array($restDays)
                ? in_array($cursor->dayOfWeekIso, $restDays, true)
                : in_array($cursor->dayOfWeek, $settings->restDayNumbers(), true);
            $isWorkingDay = is_array($workingDays)
                ? in_array($cursor->dayOfWeekIso, $workingDays, true) && ! $isRestDay
                : ! $isRestDay;
            if ($isWorkingDay) {
                $count++;
            }
            $cursor->addDay();
        }

        return $count;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  Collection<int, User>  $employees
     * @return array{name: string, value: int, unit: string}|null
     */
    private function leader(
        $rows,
        $employees,
        string $kind,
        Carbon $from,
        Carbon $to,
        PayrollSetting $settings
    ): ?array {
        $scores = [];
        foreach ($employees as $employee) {
            $scores[$employee->id] = ['user' => $employee, 'present' => 0, 'late' => 0];
        }
        foreach ($rows as $row) {
            $id = (int) $row['user_id'];
            if (! isset($scores[$id])) {
                continue;
            }
            if ((float) $row['hours'] > 0) {
                $scores[$id]['present']++;
            }
            if (! empty($row['late'])) {
                $scores[$id]['late']++;
            }
        }
        if ($scores === []) {
            return null;
        }

        $picked = collect($scores)->sortBy(match ($kind) {
            'absent' => fn (array $score) => $score['present'],
            'late' => fn (array $score) => -$score['late'],
            default => fn (array $score) => -$score['present'],
        })->first();

        if ($picked === null) {
            return null;
        }

        $value = match ($kind) {
            'late' => $picked['late'],
            'absent' => max(
                0,
                $this->workingDayCount($from, $to, $picked['user'], $settings) - $picked['present']
            ),
            default => $picked['present'],
        };
        $unit = match ($kind) {
            'late' => 'late days',
            'absent' => 'days absent',
            default => 'days',
        };

        return [
            'name' => $picked['user']->name ?: 'Employee',
            'value' => $value,
            'unit' => $unit,
        ];
    }

    /**
     * @return array{name: string, value: float, unit: string}|null
     */
    private function highestOvertime(): ?array
    {
        if (! Schema::hasTable('payslips')) {
            return null;
        }

        $row = DB::table('payslips')
            ->join('users', 'users.id', '=', 'payslips.user_id')
            ->where('users.role', User::ROLE_EMPLOYEE)
            ->selectRaw('users.name as name, SUM(payslips.overtime_hours) as h')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('h')
            ->first();

        if ($row === null) {
            return null;
        }

        return [
            'name' => (string) ($row->name ?: 'Employee'),
            'value' => round((float) $row->h, 2),
            'unit' => 'hours (payslips)',
        ];
    }

    /**
     * @return list<array{label: string, hours: float}>
     */
    private function overtimeTrend(): array
    {
        if (! Schema::hasTable('payslips')) {
            return [];
        }

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', period_end)"
            : "DATE_FORMAT(period_end, '%Y-%m')";

        return DB::table('payslips')
            ->selectRaw("{$monthExpr} as bucket, SUM(overtime_hours) as h")
            ->where('period_end', '>=', now()->subMonths(12)->toDateString())
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->bucket, 'hours' => round((float) $row->h, 2)])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{day: string, count: int}>  $daily
     * @return array<string, list<array{label: string, count: int}>>
     */
    private function series(array $daily): array
    {
        $week = [];
        $month = [];
        $year = [];
        $byDay = [];
        foreach ($daily as $row) {
            $day = Carbon::parse($row['day']);
            $byDay[] = ['label' => $day->format('M j'), 'count' => $row['count']];
            $weekKey = $day->format('o-\WW');
            $monthKey = $day->format('Y-m');
            $yearKey = $day->format('Y');
            $week[$weekKey] = ($week[$weekKey] ?? 0) + $row['count'];
            $month[$monthKey] = ($month[$monthKey] ?? 0) + $row['count'];
            $year[$yearKey] = ($year[$yearKey] ?? 0) + $row['count'];
        }

        $format = function (array $assoc): array {
            $out = [];
            foreach ($assoc as $label => $count) {
                $out[] = ['label' => (string) $label, 'count' => $count];
            }

            return $out;
        };

        return [
            'by_day' => $byDay,
            'by_week' => $format($week),
            'by_month' => $format($month),
            'by_year' => $format($year),
        ];
    }

    /**
     * @param  Collection<int, User>  $employees
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array{name: string, employees: int, attendance_rows_30d: int}>
     */
    private function departments($employees, $rows): array
    {
        $buckets = [];
        foreach ($employees as $employee) {
            $name = $employee->departmentLabel();
            $buckets[$name]['employees'] = ($buckets[$name]['employees'] ?? 0) + 1;
            $buckets[$name]['rows'] = $buckets[$name]['rows'] ?? 0;
        }
        foreach ($rows as $row) {
            $name = (string) ($row['department'] ?? 'Unassigned');
            $buckets[$name]['employees'] = $buckets[$name]['employees'] ?? 0;
            $buckets[$name]['rows'] = ($buckets[$name]['rows'] ?? 0) + 1;
        }

        $out = [];
        foreach ($buckets as $name => $bucket) {
            $out[] = [
                'name' => $name,
                'employees' => $bucket['employees'],
                'attendance_rows_30d' => $bucket['rows'],
            ];
        }

        usort($out, fn (array $a, array $b): int => $b['attendance_rows_30d'] <=> $a['attendance_rows_30d']);

        return $out;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function heatmap($rows, Carbon $from): array
    {
        $values = [];
        $max = 0;
        foreach ($rows as $row) {
            if ((float) $row['hours'] <= 0) {
                continue;
            }
            $day = Carbon::parse($row['date']);
            $week = (int) $from->diffInWeeks($day);
            $weekday = (int) $day->dayOfWeekIso - 1;
            $values[$week][$weekday] = ($values[$week][$weekday] ?? 0) + 1;
            $max = max($max, $values[$week][$weekday]);
        }

        $weekLabels = [];
        $grid = [];
        ksort($values);
        foreach ($values as $week => $days) {
            $weekLabels[] = 'W'.$week;
            $line = [];
            for ($i = 0; $i < 7; $i++) {
                $line[] = $days[$i] ?? 0;
            }
            $grid[] = $line;
        }

        return [
            'weekday_labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'week_labels' => $weekLabels,
            'values' => $grid,
            'max' => $max,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array{name: string, days: int}>
     */
    private function topPerformers($rows): array
    {
        return $rows
            ->filter(fn (array $row): bool => (float) $row['hours'] > 0)
            ->groupBy('user_id')
            ->map(function ($group): array {
                $first = $group->first();

                $late = $group->filter(fn (array $row): bool => ! empty($row['late']))->count();

                return [
                    'name' => (string) ($first['name'] ?: 'Employee'),
                    'score' => $group->count(),
                    'present' => $group->count(),
                    'late' => $late,
                ];
            })
            ->sortByDesc('score')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * Every attendance day stored in the database, newest first.
     *
     * @return list<array{employee: string, date: string, time_in: string, time_out: string, status: string}>
     */
    private function recent(PayrollSetting $settings): array
    {
        $people = $this->attendancePeople();
        $rows = collect($this->rollup->forEmployees(
            $people,
            Carbon::parse('2000-01-01')->startOfDay(),
            now()->endOfDay(),
            $settings
        ));

        return $rows
            ->sortByDesc(fn (array $row): string => $this->rowStamp($row))
            ->map(function (array $row): array {
                $timeIn = $row['time_in'] instanceof Carbon ? $row['time_in'] : null;
                $timeOut = $row['time_out'] instanceof Carbon ? $row['time_out'] : null;

                return [
                    'employee' => (string) ($row['name'] ?: 'Employee'),
                    'date' => Carbon::parse($row['date'])->format('M j, Y'),
                    'time_in' => $timeIn ? $timeIn->format('g:i A') : '—',
                    'time_out' => $timeOut ? $timeOut->format('g:i A') : '—',
                    'status' => ! empty($row['late']) ? 'Late' : ($timeIn ? 'On time' : 'Out'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Latest stored punch for each employee.
     *
     * @return list<array{name: string, last_attendance: string, status: string}>
     */
    private function statusMonitor(PayrollSetting $settings): array
    {
        $people = $this->attendancePeople();

        $history = collect($this->rollup->forEmployees(
            $people,
            Carbon::parse('2000-01-01')->startOfDay(),
            now()->endOfDay(),
            $settings
        ));

        $latest = [];
        foreach ($history as $row) {
            $id = (int) $row['user_id'];
            $stamp = $this->rowStamp($row);
            if (! isset($latest[$id]) || $stamp > $latest[$id]['stamp']) {
                $latest[$id] = ['stamp' => $stamp, 'row' => $row];
            }
        }

        return $people->map(function (User $person) use ($latest): array {
            $row = $latest[$person->id]['row'] ?? null;
            $status = 'No record';
            $last = '—';
            $sortAt = '';

            if (is_array($row)) {
                $timeIn = $row['time_in'] instanceof Carbon ? $row['time_in'] : null;
                $timeOut = $row['time_out'] instanceof Carbon ? $row['time_out'] : null;
                $clockedOut = $timeOut !== null && ($timeIn === null || $timeOut->greaterThanOrEqualTo($timeIn));
                $lastMoment = $clockedOut ? $timeOut : ($timeIn ?? $timeOut);

                if ($clockedOut) {
                    $status = 'Out';
                } elseif ($timeIn !== null && ! empty($row['late']) && $row['date'] === now()->toDateString()) {
                    $status = 'Late';
                } elseif ($timeIn !== null) {
                    $status = 'In';
                }

                if ($lastMoment !== null) {
                    $last = $lastMoment->format('M j, Y g:i A');
                    $sortAt = $lastMoment->format('Y-m-d H:i:s');
                }
            }

            return [
                'name' => $person->name ?: 'Employee',
                'last_attendance' => $last,
                'status' => $status,
                'sort_at' => $sortAt,
            ];
        })->sortBy([
            ['sort_at', 'desc'],
            ['name', 'asc'],
        ])->map(fn (array $row): array => [
            'name' => $row['name'],
            'last_attendance' => $row['last_attendance'],
            'status' => $row['status'],
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowStamp(array $row): string
    {
        $moments = [];
        foreach (['time_in', 'time_out'] as $key) {
            if (($row[$key] ?? null) instanceof Carbon) {
                $moments[] = $row[$key]->format('Y-m-d H:i:s');
            }
        }

        if ($moments === []) {
            return (string) ($row['date'] ?? '').' 00:00:00';
        }

        rsort($moments);

        return $moments[0];
    }

    /**
     * @return Collection<int, User>
     */
    private function attendancePeople()
    {
        return User::query()
            ->where('role', User::ROLE_EMPLOYEE)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array{label: string, avg_hour: float}>
     */
    private function timeInTrend($rows): array
    {
        return $rows
            ->filter(fn (array $row): bool => $row['time_in'] instanceof Carbon)
            ->groupBy('date')
            ->map(function ($group, $date): array {
                $avg = $group->avg(fn (array $row): float => ((int) $row['time_in']->format('H')) + ((int) $row['time_in']->format('i') / 60));

                return [
                    'label' => (string) $date,
                    'avg_hour' => round((float) $avg, 2),
                ];
            })
            ->sortKeys()
            ->take(-30)
            ->values()
            ->all();
    }
}
