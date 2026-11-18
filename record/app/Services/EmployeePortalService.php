<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\PayrollSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmployeePortalService
{
    public function __construct(
        private AttendanceRollupService $rollup,
        private LogbookListingService $logbookListing,
    ) {}

    /**
     * @return array{
     *     myAttendanceCount: int,
     *     latestAttendance: Collection<int, object>,
     *     monthlySummary: array{days: int, hours: float, late: int, absences: int},
     *     liveSignature: string
     * }
     */
    public function dashboard(User $user): array
    {
        $monthStart = now()->startOfMonth();
        $monthlyRows = collect($this->rollup->forUser($user, $monthStart, now()->endOfDay()));
        $activeRows = $monthlyRows->filter(
            fn (array $row): bool => (bool) ($row['valid_time_in'] ?? false)
                || (float) $row['hours'] > 0
        );
        $latestAttendance = $monthlyRows
            ->sortByDesc('date')
            ->map(fn (array $row) => (object) [
                'attendance_date' => $row['date'],
                'time_in' => $row['time_in'] ? $row['time_in']->format('Y-m-d h:i:s A') : null,
                'time_out' => $row['time_out'] ? $row['time_out']->format('Y-m-d h:i:s A') : null,
                'remarks' => trim((string) $row['remarks'].($row['source'] ? ' · '.$row['source'] : '')),
                'late' => (bool) ($row['late'] ?? false),
                'valid_time_in' => (bool) ($row['valid_time_in'] ?? false),
            ])
            ->values();
        $myAttendanceCount = $monthlyRows->filter(fn (array $row): bool => (bool) ($row['valid_time_in'] ?? false))->count();
        $monthlySummary = $this->monthlyAttendanceSummary($user, $monthStart, $monthlyRows, $activeRows);

        $payload = [
            'myAttendanceCount' => $myAttendanceCount,
            'latestAttendance' => $latestAttendance,
            'monthlySummary' => $monthlySummary,
        ];

        $payload['liveSignature'] = $this->buildDashboardSignature($payload);

        return $payload;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array{rows: Collection<int, object>, page: int, pages: int, month: string}
     */
    public function dashboardAttendancePage(Collection $rows, Request $request): array
    {
        $month = now()->format('Y-m');
        $requestedMonth = $request->query('week_month');
        $page = $requestedMonth === $month ? max(1, $request->integer('week_page', 1)) : 1;
        $pages = max(1, (int) ceil($rows->count() / 7));
        $page = min($page, $pages);

        return [
            'rows' => $rows->forPage($page, 7)->values(),
            'page' => $page,
            'pages' => $pages,
            'month' => $month,
        ];
    }

    /**
     * @param  array{myAttendanceCount: int, latestAttendance: Collection<int, object>, monthlySummary: array{days: int, hours: float, late: int, absences: int}}  $payload
     */
    private function buildDashboardSignature(array $payload): string
    {
        $latest = $payload['latestAttendance']->map(function ($row) {
            return [
                'attendance_date' => (string) ($row->attendance_date ?? ''),
                'time_in' => (string) ($row->time_in ?? ''),
                'time_out' => (string) ($row->time_out ?? ''),
                'remarks' => (string) ($row->remarks ?? ''),
            ];
        })->all();

        return sha1(json_encode([
            'myAttendanceCount' => $payload['myAttendanceCount'],
            'month' => now()->format('Y-m'),
            'monthlySummary' => $payload['monthlySummary'],
            'latest' => $latest,
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array{
     *     logDailySummaries: Collection<int, object>,
     *     liveSignature: string
     * }
     */
    public function attendancesIndex(User $user): array
    {
        $rolled = collect($this->rollup->forUser($user, now()->subYear()->startOfDay(), now()->endOfDay()))
            ->sortByDesc('date')
            ->map(fn (array $row) => (object) [
                'attendance_date' => $row['date'],
                'time_in' => $row['time_in'],
                'time_out' => $row['time_out'],
                'remarks' => trim((string) ($row['remarks'] ?? '')),
                'source' => $row['source'],
                'hours' => $row['hours'],
                'late' => (bool) ($row['late'] ?? false),
                'valid_time_in' => (bool) ($row['valid_time_in'] ?? false),
                'holiday_name' => $row['holiday_name'] ?? null,
            ])
            ->values();

        $payload = [
            'logDailySummaries' => $rolled,
        ];

        $payload['liveSignature'] = $this->buildAttendancesSignature($payload);

        return $payload;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateAttendanceSummaries(Collection $rows, Request $request, int $perPage): LengthAwarePaginator
    {
        $page = max(1, $request->integer('attendance_page', 1));

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'attendance_page',
                'query' => $request->query(),
            ],
        );
    }

    private function buildAttendancesSignature(array $payload): string
    {
        return sha1(json_encode([
            'rows' => collect($payload['logDailySummaries'])->map(fn ($row) => [
                'attendance_date' => (string) ($row->attendance_date ?? ''),
                'time_in' => (string) ($row->time_in ?? ''),
                'time_out' => (string) ($row->time_out ?? ''),
                'hours' => (string) ($row->hours ?? ''),
            ])->all(),
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $monthlyRows
     * @param  Collection<int, array<string, mixed>>  $activeRows
     * @return array{days: int, hours: float, late: int, absences: int}
     */
    private function monthlyAttendanceSummary(User $user, Carbon $monthStart, Collection $monthlyRows, Collection $activeRows): array
    {
        return [
            'days' => $activeRows->count(),
            'hours' => round((float) $monthlyRows->sum(fn (array $row): float => (float) $row['hours']), 1),
            'late' => $activeRows->filter(fn (array $row): bool => (bool) ($row['late'] ?? false))->count(),
            'absences' => $this->monthlyAbsenceCount($user, $monthStart, $activeRows),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $workedRows
     */
    private function monthlyAbsenceCount(User $user, Carbon $monthStart, Collection $workedRows): int
    {
        $today = now()->startOfDay();
        $lastCompletedDay = $today->copy()->subDay();
        if ($lastCompletedDay->lt($monthStart)) {
            return 0;
        }

        $settings = PayrollSetting::current();
        $workingDays = $user->working_days;
        $restDays = $user->employee_rest_days;
        $holidayDates = collect();
        if (Schema::hasTable('holidays')) {
            $holidayDates = Holiday::query()
                ->whereDate('holiday_date', '>=', $monthStart->toDateString())
                ->whereDate('holiday_date', '<=', $lastCompletedDay->toDateString())
                ->pluck('holiday_date')
                ->map(fn ($date): string => Carbon::parse($date)->toDateString())
                ->flip();
        }

        $workedDates = $workedRows
            ->map(fn (array $row): string => (string) $row['date'])
            ->flip();
        $absences = 0;

        for ($day = $monthStart->copy()->startOfDay(); $day->lte($lastCompletedDay); $day->addDay()) {
            $isRestDay = is_array($restDays)
                ? in_array($day->dayOfWeekIso, $restDays, true)
                : in_array($day->dayOfWeek, $settings->restDayNumbers(), true);
            $isWorkingDay = is_array($workingDays)
                ? in_array($day->dayOfWeekIso, $workingDays, true) && ! $isRestDay
                : ! $isRestDay;

            if ($isWorkingDay && ! $holidayDates->has($day->toDateString()) && ! $workedDates->has($day->toDateString())) {
                $absences++;
            }
        }

        return $absences;
    }

    /**
     * @return array{
     *     legacyEntries: ?LengthAwarePaginator,
     *     entries: ?LengthAwarePaginator,
     *     accounts: ?LengthAwarePaginator,
     *     search: string,
     *     liveSignature: string
     * }
     */
    public function logbooksIndex(User $user, Request $request): array
    {
        $pageSizes = [5, 10, 15, 20, 25, 50, 75, 100];
        $search = trim($request->string('search')->toString());
        $globalPerPage = in_array((int) $request->query('per_page'), $pageSizes, true)
            ? (int) $request->query('per_page')
            : 10;
        $legacyPerPage = $globalPerPage;
        $accountsPerPage = $globalPerPage;
        $legacyEntries = Schema::hasTable('logbook')
            ? $this->logbookListing->paginateLegacyEntries($search, $legacyPerPage, 'legacy_page')
            : null;
        $entries = Schema::hasTable('logbook_entries')
            ? $this->logbookListing->paginateAppEntries($search, $legacyPerPage, 'entries_page')
            : null;
        $accounts = $this->paginateLogbookAccounts($search, $accountsPerPage);

        $payload = [
            'legacyEntries' => $legacyEntries,
            'entries' => $entries,
            'accounts' => $accounts,
            'search' => $search,
            'legacyPerPage' => $legacyPerPage,
            'accountsPerPage' => $accountsPerPage,
            'globalPerPage' => $globalPerPage,
            'pageSizes' => $pageSizes,
        ];
        $payload['liveSignature'] = sha1(json_encode([
            'search' => $search,
            'legacy' => $legacyEntries === null
                ? null
                : $this->logbookListing->legacyLiveSignature($legacyEntries, $search),
            'entries' => $entries === null
                ? null
                : $this->logbookListing->appLiveSignature($entries, $search),
            'accounts' => $accounts === null ? null : [
                'total' => $accounts->total(),
                'page' => $accounts->currentPage(),
                'items' => $accounts->getCollection()->map(fn ($account) => [
                    'full_name' => (string) ($account->full_name ?? ''),
                    'address' => (string) ($account->address ?? ''),
                    'contact_number' => (string) ($account->contact_number ?? ''),
                    'person_category' => (string) ($account->person_category ?? ''),
                    'transaction_count' => (int) ($account->transaction_count ?? 0),
                    'last_seen' => (string) ($account->last_seen ?? ''),
                ])->all(),
            ],
        ], JSON_UNESCAPED_UNICODE));

        return $payload;
    }

    private function paginateLogbookAccounts(string $search, int $perPage): ?LengthAwarePaginator
    {
        $sources = [];
        foreach (['logbook', 'logbook_entries', 'visitor_accounts'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'full_name')) {
                continue;
            }

            $address = Schema::hasColumn($table, 'address') ? "{$table}.address" : 'NULL';
            $contact = Schema::hasColumn($table, 'contact_number') ? "{$table}.contact_number" : 'NULL';
            $time = Schema::hasColumn($table, 'time_in')
                ? "{$table}.time_in"
                : (Schema::hasColumn($table, 'entry_at') ? "{$table}.entry_at" : 'NULL');
            $categoryColumn = collect(['person_category', 'person_classification'])
                ->first(fn (string $column): bool => Schema::hasColumn($table, $column));
            $category = $table === 'visitor_accounts'
                ? "'Visitor'"
                : ($categoryColumn === null ? 'NULL' : "{$table}.{$categoryColumn}");

            $source = DB::table($table)->selectRaw(
                "COALESCE({$table}.full_name, '') as full_name, COALESCE({$address}, '') as address, ".
                "COALESCE({$contact}, '') as contact_number, COALESCE({$category}, '') as person_category, ".
                "{$time} as last_seen"
            );
            if ($table !== 'visitor_accounts') {
                $source->whereNotNull("{$table}.full_name")
                    ->whereRaw("TRIM({$table}.full_name) <> ''");
            }
            $sources[] = $source;
        }

        if ($sources === []) {
            return null;
        }

        $source = array_shift($sources);
        foreach ($sources as $nextSource) {
            $source->unionAll($nextSource);
        }

        $accounts = DB::query()->fromSub($source, 'logbook_accounts');
        if ($search !== '') {
            $accounts->where(function ($query) use ($search): void {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->orWhere('person_category', 'like', "%{$search}%");
            });
        }

        return $accounts
            ->selectRaw('full_name, address, contact_number, person_category, COUNT(*) as transaction_count, MAX(last_seen) as last_seen')
            ->groupBy('full_name', 'address', 'contact_number', 'person_category')
            ->orderBy('full_name')
            ->paginate($perPage, ['*'], 'accounts_page')
            ->withQueryString();
    }
}
