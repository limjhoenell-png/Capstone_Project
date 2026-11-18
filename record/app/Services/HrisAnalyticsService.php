<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HrisAnalyticsService
{
    private const ANALYSIS_DAYS = 30;

    private const LATE_AFTER = '09:15:00';

    /**
     * Full analytics payload for the admin dashboard (JSON).
     *
     * @return array<string, mixed>
     */
    public function dashboard(string $reportPeriod = 'month'): array
    {
        $reportPeriod = in_array($reportPeriod, ['day', 'week', 'month', 'year'], true)
            ? $reportPeriod
            : 'month';

        $employees = User::query()->where('role', User::ROLE_EMPLOYEE)->orderBy('name');
        $employeeCount = (clone $employees)->count();

        $base = [
            'generated_at' => now()->toIso8601String(),
            'report_period' => $reportPeriod,
            'summary' => [
                'total_employees' => $employeeCount,
                'present_today' => 0,
                'absent_today' => max(0, $employeeCount),
                'attendance_rate_pct' => 0.0,
                'late_rate_pct' => 0.0,
            ],
            'leaders' => [
                'most_present' => null,
                'most_absent' => null,
                'most_late' => null,
                'highest_overtime' => null,
                'highest_salary' => null,
                'lowest_salary' => null,
            ],
            'payroll' => [
                'payslip_count' => 0,
                'total_net_pay' => 0.0,
                'total_gross_pay' => 0.0,
                'avg_net_pay' => 0.0,
            ],
            'salary_distribution' => [],
            'attendance_series' => $this->emptyRollup(),
            'payroll_series' => $this->emptyRollup(),
            'overtime_trend' => [],
            'department' => [],
            'heatmap' => ['weekday_labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 'week_labels' => [], 'values' => [], 'max' => 0],
            'top_performers' => [],
            'recent_activity' => [],
            'status_monitor' => [],
            'time_in_trend' => [],
            'insights' => [],
        ];

        $base = array_replace_recursive($base, app(HrisRollupAnalytics::class)->build($employeeCount, $reportPeriod));

        if (Schema::hasTable('payslips')) {
            $base = array_replace_recursive($base, $this->payrollAnalytics());
        }

        $base['leaders'] = array_replace($base['leaders'], $this->salaryLeaders());
        $base['insights'] = $this->buildInsights($base);

        return $base;
    }

    /**
     * @return array{by_day: list<array{label: string, count: int}>, by_week: list, by_month: list, by_year: list}
     */
    private function emptyRollup(): array
    {
        return [
            'by_day' => [],
            'by_week' => [],
            'by_month' => [],
            'by_year' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceAnalytics(int $employeeCount, string $since, string $today): array
    {
        $presentToday = (int) DB::table('attendances')
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->where('users.role', User::ROLE_EMPLOYEE)
            ->whereDate('attendances.attendance_date', $today)
            ->selectRaw('COUNT(DISTINCT attendances.user_id) as c')
            ->value('c');

        $absentToday = max(0, $employeeCount - $presentToday);

        $distinctDays = (int) DB::table('attendances')
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->where('users.role', User::ROLE_EMPLOYEE)
            ->where('attendances.attendance_date', '>=', $since)
            ->selectRaw('COUNT(DISTINCT attendances.user_id || "|" || attendances.attendance_date) as c')
            ->value('c');

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $distinctDays = (int) DB::table('attendances')
                ->join('users', 'users.id', '=', 'attendances.user_id')
                ->where('users.role', User::ROLE_EMPLOYEE)
                ->where('attendances.attendance_date', '>=', $since)
                ->selectRaw('COUNT(DISTINCT CONCAT(attendances.user_id, "|", attendances.attendance_date)) as c')
                ->value('c');
        }

        $expectedSlots = max(1, $employeeCount * 22);
        $attendanceRate = round(min(100.0, ($distinctDays / $expectedSlots) * 100), 1);

        $withTimeIn = (int) DB::table('attendances')
            ->where('attendance_date', '>=', $since)
            ->whereNotNull('time_in')
            ->count();

        $lateCount = (int) DB::table('attendances')
            ->where('attendance_date', '>=', $since)
            ->whereNotNull('time_in')
            ->whereTime('time_in', '>', self::LATE_AFTER)
            ->count();

        $lateRate = $withTimeIn > 0 ? round($lateCount / $withTimeIn * 100, 1) : 0.0;

        $daily = DB::table('attendances')
            ->selectRaw($this->attendanceDateDayExpr().' as day_bucket, COUNT(*) as c')
            ->where('attendance_date', '>=', now()->subDays(120)->toDateString())
            ->groupBy('day_bucket')
            ->orderBy('day_bucket')
            ->get();

        $rollup = $this->rollupFromDaily($daily->map(fn ($r) => [
            'day' => (string) $r->day_bucket,
            'count' => (int) $r->c,
        ])->all());

        $mostPresent = DB::table('attendances')
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->where('users.role', User::ROLE_EMPLOYEE)
            ->where('attendances.attendance_date', '>=', $since)
            ->selectRaw('users.id as user_id, users.name as name, users.full_name as full_name, COUNT(*) as c')
            ->groupBy('users.id', 'users.name', 'users.full_name')
            ->orderByDesc('c')
            ->first();

        $mostAbsent = DB::table('users')
            ->leftJoin('attendances', function ($join) use ($since): void {
                $join->on('attendances.user_id', '=', 'users.id')
                    ->where('attendances.attendance_date', '>=', $since);
            })
            ->where('users.role', User::ROLE_EMPLOYEE)
            ->selectRaw('users.id, users.name, users.full_name, COUNT(attendances.id) as c')
            ->groupBy('users.id', 'users.name', 'users.full_name')
            ->orderBy('c')
            ->first();

        $mostLate = DB::table('attendances')
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->where('users.role', User::ROLE_EMPLOYEE)
            ->where('attendances.attendance_date', '>=', $since)
            ->whereNotNull('attendances.time_in')
            ->whereTime('attendances.time_in', '>', self::LATE_AFTER)
            ->selectRaw('users.id, users.name, users.full_name, COUNT(*) as c')
            ->groupBy('users.id', 'users.name', 'users.full_name')
            ->orderByDesc('c')
            ->first();

        $highestOt = null;
        if (Schema::hasTable('payslips')) {
            $highestOt = DB::table('payslips')
                ->join('users', 'users.id', '=', 'payslips.user_id')
                ->where('users.role', User::ROLE_EMPLOYEE)
                ->selectRaw('users.name, users.full_name, SUM(payslips.overtime_hours) as h')
                ->groupBy('users.id', 'users.name', 'users.full_name')
                ->orderByDesc('h')
                ->first();
        }

        $heatmap = $this->buildHeatmap($since);
        $topPerformers = $this->buildTopPerformers($since);
        $recent = $this->recentActivity();
        $status = $this->statusMonitor();
        $timeInTrend = $this->timeInTrend();

        $otTrend = [];
        if (Schema::hasTable('payslips')) {
            $driver = DB::connection()->getDriverName();
            $monthExpr = $driver === 'sqlite'
                ? "strftime('%Y-%m', period_end)"
                : "DATE_FORMAT(period_end, '%Y-%m')";
            $otTrend = DB::table('payslips')
                ->selectRaw("{$monthExpr} as bucket, SUM(overtime_hours) as h")
                ->where('period_end', '>=', now()->subMonths(12)->toDateString())
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get()
                ->map(fn ($r) => ['label' => (string) $r->bucket, 'hours' => round((float) $r->h, 2)])
                ->values()
                ->all();
        }

        $department = DB::table('users')
            ->leftJoin('attendances', function ($join) use ($since): void {
                $join->on('attendances.user_id', '=', 'users.id')
                    ->where('attendances.attendance_date', '>=', $since);
            })
            ->where('users.role', User::ROLE_EMPLOYEE)
            ->selectRaw('COALESCE(NULLIF(TRIM(users.position), ""), "Unassigned") as dept, COUNT(DISTINCT users.id) as ec, COUNT(attendances.id) as ac')
            ->groupBy('dept')
            ->orderByDesc('ac')
            ->get()
            ->map(fn ($r) => [
                'name' => (string) $r->dept,
                'employees' => (int) $r->ec,
                'attendance_rows_30d' => (int) $r->ac,
            ])
            ->values()
            ->all();

        return [
            'summary' => [
                'total_employees' => $employeeCount,
                'present_today' => $presentToday,
                'absent_today' => $absentToday,
                'attendance_rate_pct' => $attendanceRate,
                'late_rate_pct' => $lateRate,
            ],
            'leaders' => [
                'most_present' => $this->leaderFromUserRow($mostPresent, 'c', 'days'),
                'most_absent' => $this->leaderFromUserRow($mostAbsent, 'c', 'rows', true),
                'most_late' => $this->leaderFromUserRow($mostLate, 'c', 'late days'),
                'highest_overtime' => $highestOt ? [
                    'name' => $this->displayName($highestOt),
                    'value' => round((float) $highestOt->h, 2),
                    'unit' => 'hours (payslips)',
                ] : null,
            ],
            'attendance_series' => $rollup,
            'overtime_trend' => $otTrend,
            'department' => $department,
            'heatmap' => $heatmap,
            'top_performers' => $topPerformers,
            'recent_activity' => $recent,
            'status_monitor' => $status,
            'time_in_trend' => $timeInTrend,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payrollAnalytics(): array
    {
        $totals = DB::table('payslips')->selectRaw('
            COUNT(*) as c,
            COALESCE(SUM(net_pay), 0) as net,
            COALESCE(SUM(gross_pay), 0) as gross
        ')->first();

        $c = (int) ($totals->c ?? 0);
        $net = (float) ($totals->net ?? 0);
        $gross = (float) ($totals->gross ?? 0);

        $driver = DB::connection()->getDriverName();
        $dayExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', processed_at)"
            : 'DATE(processed_at)';

        $daily = DB::table('payslips')
            ->selectRaw("{$dayExpr} as day_bucket, COUNT(*) as c, COALESCE(SUM(net_pay), 0) as net")
            ->where('processed_at', '>=', now()->subDays(120)->toDateTimeString())
            ->groupBy('day_bucket')
            ->orderBy('day_bucket')
            ->get();

        $rollup = $this->rollupPayrollFromDaily($daily->map(fn ($r) => [
            'day' => (string) $r->day_bucket,
            'count' => (int) $r->c,
            'net' => (float) $r->net,
        ])->all());

        $distribution = $this->salaryBuckets();

        return [
            'payroll' => [
                'payslip_count' => $c,
                'total_net_pay' => round($net, 2),
                'total_gross_pay' => round($gross, 2),
                'avg_net_pay' => $c > 0 ? round($net / $c, 2) : 0.0,
            ],
            'payroll_series' => $rollup,
            'salary_distribution' => $distribution,
        ];
    }

    /**
     * @return array<string, array{name: string, value: float|string, unit?: string}|null>
     */
    private function salaryLeaders(): array
    {
        $hi = User::query()
            ->where('role', User::ROLE_EMPLOYEE)
            ->whereNotNull('pay_rate')
            ->orderByDesc('pay_rate')
            ->first();

        $lo = User::query()
            ->where('role', User::ROLE_EMPLOYEE)
            ->whereNotNull('pay_rate')
            ->orderBy('pay_rate')
            ->first();

        return [
            'highest_salary' => $hi ? [
                'name' => $this->userDisplayName($hi),
                'value' => (float) $hi->pay_rate,
                'unit' => (string) ($hi->pay_rate_type ?? 'rate'),
            ] : null,
            'lowest_salary' => $lo ? [
                'name' => $this->userDisplayName($lo),
                'value' => (float) $lo->pay_rate,
                'unit' => (string) ($lo->pay_rate_type ?? 'rate'),
            ] : null,
        ];
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function salaryBuckets(): array
    {
        if (! Schema::hasTable('payslips')) {
            return [];
        }

        $rows = Payslip::query()
            ->selectRaw('user_id, MAX(period_end) as pe')
            ->groupBy('user_id')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $latestNet = [];
        foreach ($rows as $row) {
            $p = Payslip::query()
                ->where('user_id', $row->user_id)
                ->where('period_end', $row->pe)
                ->orderByDesc('id')
                ->first();
            if ($p !== null) {
                $latestNet[] = (float) $p->net_pay;
            }
        }

        $buckets = [
            '0 – 10k' => 0,
            '10k – 25k' => 0,
            '25k – 50k' => 0,
            '50k – 100k' => 0,
            '100k+' => 0,
        ];

        foreach ($latestNet as $net) {
            if ($net < 10000) {
                $buckets['0 – 10k']++;
            } elseif ($net < 25000) {
                $buckets['10k – 25k']++;
            } elseif ($net < 50000) {
                $buckets['25k – 50k']++;
            } elseif ($net < 100000) {
                $buckets['50k – 100k']++;
            } else {
                $buckets['100k+']++;
            }
        }

        return collect($buckets)->map(fn (int $c, string $label) => ['label' => $label, 'count' => $c])->values()->all();
    }

    private function attendanceDateDayExpr(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', attendance_date)"
            : 'DATE(attendance_date)';
    }

    /**
     * @param  list<array{day: string, count: int}>  $dailyRows
     * @return array{by_day: list<array{label: string, count: int}>, by_week: list<array{label: string, count: int}>, by_month: list<array{label: string, count: int}>, by_year: list<array{label: string, count: int}>}
     */
    private function rollupFromDaily(array $dailyRows): array
    {
        $weekBuckets = [];
        $monthBuckets = [];
        $yearBuckets = [];

        foreach ($dailyRows as $row) {
            $day = $row['day'];
            $count = $row['count'];
            if ($day === '') {
                continue;
            }
            try {
                $c = Carbon::parse($day)->startOfDay();
            } catch (\Throwable) {
                continue;
            }

            $wk = $c->copy()->locale('en')->isoFormat('GGGG-[W]WW');
            $weekBuckets[$wk] = ($weekBuckets[$wk] ?? 0) + $count;

            $mo = $c->format('Y-m');
            $monthBuckets[$mo] = ($monthBuckets[$mo] ?? 0) + $count;

            $yr = $c->format('Y');
            $yearBuckets[$yr] = ($yearBuckets[$yr] ?? 0) + $count;
        }

        $sortAndFormat = function (array $assoc, int $limit): array {
            ksort($assoc);

            return Collection::make($assoc)
                ->take(-$limit)
                ->map(fn (int $c, string $label) => ['label' => $label, 'count' => $c])
                ->values()
                ->all();
        };

        $byDay = Collection::make($dailyRows)
            ->sortBy('day')
            ->take(-30)
            ->map(fn (array $r) => ['label' => $r['day'], 'count' => $r['count']])
            ->values()
            ->all();

        return [
            'by_day' => $byDay,
            'by_week' => $sortAndFormat($weekBuckets, 12),
            'by_month' => $sortAndFormat($monthBuckets, 18),
            'by_year' => $sortAndFormat($yearBuckets, 10),
        ];
    }

    /**
     * @param  list<array{day: string, count: int, net: float}>  $dailyRows
     * @return array{by_day: list<array{label: string, count: int, net: float}>, by_week: list, by_month: list, by_year: list}
     */
    private function rollupPayrollFromDaily(array $dailyRows): array
    {
        $weekBuckets = [];
        $monthBuckets = [];
        $yearBuckets = [];

        foreach ($dailyRows as $row) {
            $day = $row['day'];
            $count = $row['count'];
            $net = $row['net'];
            if ($day === '') {
                continue;
            }
            try {
                $c = Carbon::parse($day)->startOfDay();
            } catch (\Throwable) {
                continue;
            }

            $wk = $c->copy()->locale('en')->isoFormat('GGGG-[W]WW');
            $this->addPayrollBucket($weekBuckets, $wk, $count, $net);

            $mo = $c->format('Y-m');
            $this->addPayrollBucket($monthBuckets, $mo, $count, $net);

            $yr = $c->format('Y');
            $this->addPayrollBucket($yearBuckets, $yr, $count, $net);
        }

        $formatPay = function (array $assoc, int $limit): array {
            ksort($assoc);

            return Collection::make($assoc)
                ->take(-$limit)
                ->map(fn (array $v, string $label) => [
                    'label' => $label,
                    'count' => $v['count'],
                    'net' => round($v['net'], 2),
                ])
                ->values()
                ->all();
        };

        $byDay = Collection::make($dailyRows)
            ->sortBy('day')
            ->take(-30)
            ->map(fn (array $r) => ['label' => $r['day'], 'count' => $r['count'], 'net' => round($r['net'], 2)])
            ->values()
            ->all();

        return [
            'by_day' => $byDay,
            'by_week' => $formatPay($weekBuckets, 12),
            'by_month' => $formatPay($monthBuckets, 18),
            'by_year' => $formatPay($yearBuckets, 10),
        ];
    }

    /**
     * @param  array<string, array{count: int, net: float}>  $bucket
     */
    private function addPayrollBucket(array &$bucket, string $key, int $count, float $net): void
    {
        if (! isset($bucket[$key])) {
            $bucket[$key] = ['count' => 0, 'net' => 0.0];
        }
        $bucket[$key]['count'] += $count;
        $bucket[$key]['net'] += $net;
    }

    /**
     * @return array{weekday_labels: list<string>, week_labels: list<string>, values: list<list<int>>, max: int}
     */
    private function buildHeatmap(string $since): array
    {
        if (! Schema::hasTable('attendances')) {
            return ['weekday_labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 'week_labels' => [], 'values' => [], 'max' => 0];
        }

        $rows = DB::table('attendances')
            ->where('attendance_date', '>=', $since)
            ->select('attendance_date')
            ->get();

        $matrix = [];
        $max = 0;
        foreach ($rows as $r) {
            try {
                $d = Carbon::parse($r->attendance_date)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
            $weekStart = $d->copy()->locale('en')->isoFormat('GGGG-[W]WW');
            $wd = (int) $d->dayOfWeekIso;
            $matrix[$weekStart][$wd] = ($matrix[$weekStart][$wd] ?? 0) + 1;
            $max = max($max, $matrix[$weekStart][$wd]);
        }

        ksort($matrix);
        $weekKeys = array_slice(array_keys($matrix), -8);
        $values = [];
        foreach ($weekKeys as $wk) {
            $row = [];
            for ($d = 1; $d <= 7; $d++) {
                $row[] = (int) ($matrix[$wk][$d] ?? 0);
            }
            $values[] = $row;
        }

        return [
            'weekday_labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'week_labels' => $weekKeys,
            'values' => $values,
            'max' => $max,
        ];
    }

    /**
     * @return list<array{name: string, score: float, present: int, late: int}>
     */
    private function buildTopPerformers(string $since): array
    {
        if (! Schema::hasTable('attendances')) {
            return [];
        }

        $users = User::query()->where('role', User::ROLE_EMPLOYEE)->get(['id', 'name', 'full_name']);
        $scores = [];

        foreach ($users as $u) {
            $present = (int) DB::table('attendances')
                ->where('user_id', $u->id)
                ->where('attendance_date', '>=', $since)
                ->count();

            $late = (int) DB::table('attendances')
                ->where('user_id', $u->id)
                ->where('attendance_date', '>=', $since)
                ->whereNotNull('time_in')
                ->whereTime('time_in', '>', self::LATE_AFTER)
                ->count();

            $score = max(0, $present * 10 - $late * 4);
            $scores[] = [
                'name' => $this->userDisplayName($u),
                'score' => round($score, 1),
                'present' => $present,
                'late' => $late,
            ];
        }

        usort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scores, 0, 8);
    }

    /**
     * @return list<array{employee: string, date: string, time_in: string, status: string}>
     */
    private function recentActivity(): array
    {
        if (! Schema::hasTable('attendances')) {
            return [];
        }

        return DB::table('attendances')
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->where('users.role', User::ROLE_EMPLOYEE)
            ->orderByDesc('attendances.attendance_date')
            ->orderByDesc('attendances.time_in')
            ->limit(12)
            ->get(['users.name', 'users.full_name', 'attendances.attendance_date', 'attendances.time_in'])
            ->map(function ($r) {
                $name = trim((string) ($r->name ?? ''));
                if ($name === '' && isset($r->full_name)) {
                    $name = trim((string) $r->full_name);
                }
                if ($name === '') {
                    $name = 'Employee';
                }
                $ti = $r->time_in ? Carbon::parse($r->time_in)->format('Y-m-d h:i A') : '—';
                $late = $r->time_in && Carbon::parse($r->time_in)->format('H:i:s') > self::LATE_AFTER;

                return [
                    'employee' => $name,
                    'date' => (string) $r->attendance_date,
                    'time_in' => $ti,
                    'status' => $late ? 'Late' : 'On time',
                ];
            })
            ->all();
    }

    /**
     * @return list<array{name: string, last_attendance: string, status: string}>
     */
    private function statusMonitor(): array
    {
        if (! Schema::hasTable('attendances')) {
            return [];
        }

        return User::query()
            ->where('role', User::ROLE_EMPLOYEE)
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function (User $u) {
                $last = DB::table('attendances')
                    ->where('user_id', $u->id)
                    ->orderByDesc('attendance_date')
                    ->value('attendance_date');

                $status = 'No attendance';
                if ($last !== null) {
                    $days = Carbon::parse($last)->diffInDays(now());
                    $status = $days === 0 ? 'Active today' : ($days <= 7 ? 'Recent' : 'Stale');
                }

                return [
                    'name' => $this->userDisplayName($u),
                    'last_attendance' => $last ? (string) $last : '—',
                    'status' => $status,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{label: string, avg_hour: float}>
     */
    private function timeInTrend(): array
    {
        if (! Schema::hasTable('attendances')) {
            return [];
        }

        $driver = DB::connection()->getDriverName();
        $dayExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', attendance_date)"
            : 'DATE(attendance_date)';

        if ($driver === 'sqlite') {
            $rows = DB::table('attendances')
                ->whereNotNull('time_in')
                ->where('attendance_date', '>=', now()->subDays(14)->toDateString())
                ->selectRaw("{$dayExpr} as d, AVG(CAST(strftime('%H', time_in) AS REAL) + CAST(strftime('%M', time_in) AS REAL) / 60.0) as avgh")
                ->groupBy('d')
                ->orderBy('d')
                ->get();
        } else {
            $rows = DB::table('attendances')
                ->whereNotNull('time_in')
                ->where('attendance_date', '>=', now()->subDays(14)->toDateString())
                ->selectRaw('DATE(attendance_date) as d, AVG(TIME_TO_SEC(time_in) / 3600) as avgh')
                ->groupBy('d')
                ->orderBy('d')
                ->get();
        }

        return $rows->map(fn ($r) => [
            'label' => (string) $r->d,
            'avg_hour' => round((float) $r->avgh, 2),
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function buildInsights(array $payload): array
    {
        $lines = [];
        $s = $payload['summary'] ?? [];
        if (($s['late_rate_pct'] ?? 0) > 15) {
            $lines[] = 'Late arrivals exceed 15% in the last '.self::ANALYSIS_DAYS.' days — consider shift communication or grace policy review.';
        }
        if (($s['attendance_rate_pct'] ?? 0) < 60 && ($s['total_employees'] ?? 0) > 0) {
            $lines[] = 'Overall attendance coverage is below 60% vs. expected slots — verify device sync and attendance capture.';
        }
        if (($payload['payroll']['payslip_count'] ?? 0) === 0 && Schema::hasTable('payslips')) {
            $lines[] = 'No payslips on record yet — run payroll processing to unlock salary distribution analytics.';
        }
        if (($s['present_today'] ?? 0) > 0) {
            $lines[] = $s['present_today'].' employee(s) checked in today.';
        }
        if ($lines === []) {
            $lines[] = 'Signals are nominal — keep monitoring attendance and payroll trends weekly.';
        }

        return $lines;
    }

    private function userDisplayName(User $u): string
    {
        $n = trim((string) ($u->name ?? ''));
        if ($n !== '') {
            return $n;
        }
        if (Schema::hasColumn('users', 'full_name')) {
            $fn = trim((string) ($u->full_name ?? ''));
            if ($fn !== '') {
                return $fn;
            }
        }

        return 'Employee #'.$u->id;
    }

    private function displayName(object $row): string
    {
        $n = trim((string) ($row->name ?? ''));
        if ($n !== '') {
            return $n;
        }
        if (property_exists($row, 'full_name')) {
            $fn = trim((string) ($row->full_name ?? ''));
            if ($fn !== '') {
                return $fn;
            }
        }

        return 'Employee';
    }

    /**
     * @param  object|null  $row
     * @return array{name: string, value: int, unit: string}|null
     */
    private function leaderFromUserRow(?object $row, string $countKey, string $unit, bool $invertLabel = false): ?array
    {
        if ($row === null) {
            return null;
        }

        $name = $this->displayName($row);
        $v = (int) ($row->{$countKey} ?? 0);

        return [
            'name' => $name,
            'value' => $v,
            'unit' => $invertLabel ? 'attendance rows ('.self::ANALYSIS_DAYS.'d, lowest)' : $unit,
        ];
    }
}
