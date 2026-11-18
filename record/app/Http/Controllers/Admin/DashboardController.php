<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LogbookEntry;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Services\HrisAnalyticsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private HrisAnalyticsService $hrisAnalytics,
    ) {}

    public function index(): View
    {
        $settings = PayrollSetting::current();
        $shift = Carbon::parse((string) $settings->shift_start)->format('g:i A');

        return view('admin.dashboard', [
            'employeeCount' => User::query()->where('role', User::ROLE_EMPLOYEE)->count(),
            'lateTodayCount' => $this->lateTodayCount($settings),
            'logbookCount' => $this->logbookAccountCount(),
            'attendancePunches' => $this->latestAttendancePunches(),
            'logbookTransactions' => $this->latestLogbookTransactions(),
            'hrisFigures' => $this->hrisAnalytics->dashboard('month'),
            'lateCutoffLabel' => $shift.' + '.(int) $settings->grace_minutes.' min',
        ]);
    }

    private function lateTodayCount(PayrollSetting $settings): int
    {
        $today = now();
        $cutoff = $today->copy()
            ->setTimeFromTimeString((string) $settings->shift_start)
            ->addMinutes((int) $settings->grace_minutes);

        return Attendance::query()
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->where('users.role', User::ROLE_EMPLOYEE)
            ->whereDate('attendances.attendance_date', $today->toDateString())
            ->whereNotNull('attendances.time_in')
            ->where('attendances.time_in', '>', $cutoff->toDateTimeString())
            ->distinct('attendances.user_id')
            ->count('attendances.user_id');
    }

    /**
     * @return Collection<int, array{name: string, date: string, time_in: string, time_out: string}>
     */
    private function latestAttendancePunches(): Collection
    {
        return Attendance::query()
            ->with('user')
            ->whereHas('user', fn ($query) => $query->where('role', User::ROLE_EMPLOYEE))
            ->orderByDesc('attendance_date')
            ->orderByDesc('time_in')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (Attendance $attendance): array => [
                'name' => (string) ($attendance->user?->name ?: 'Employee'),
                'date' => $attendance->attendance_date?->format('M j, Y') ?? '—',
                'time_in' => $attendance->time_in?->format('g:i A') ?? '—',
                'time_out' => $attendance->time_out?->format('g:i A') ?? '—',
            ]);
    }

    private function logbookAccountCount(): int
    {
        $table = Schema::hasTable('logbook')
            ? 'logbook'
            : (Schema::hasTable('logbook_entries') ? 'logbook_entries' : null);

        if ($table === null || ! Schema::hasColumn($table, 'full_name')) {
            return 0;
        }

        $categoryColumn = $table === 'logbook'
            ? collect(['person_category', 'person_classification'])
                ->first(fn (string $column): bool => Schema::hasColumn($table, $column))
            : (Schema::hasColumn($table, 'person_category') ? 'person_category' : null);
        $contactExpression = Schema::hasColumn($table, 'contact_number')
            ? "LOWER(TRIM(COALESCE(contact_number, '')))"
            : "''";
        $profiles = DB::table($table)
            ->selectRaw('LOWER(TRIM(full_name)) as account_name')
            ->selectRaw("{$contactExpression} as account_contact")
            ->whereNotNull('full_name')
            ->whereRaw("TRIM(full_name) <> ''");

        $groupBy = ['LOWER(TRIM(full_name))', $contactExpression];
        if ($categoryColumn !== null) {
            $categoryExpression = "LOWER(TRIM(COALESCE({$categoryColumn}, '')))";
            $profiles->selectRaw("{$categoryExpression} as account_category")
                ->whereIn(DB::raw($categoryExpression), ['personnel', 'visitor', 'resident']);
            $groupBy[] = $categoryExpression;
        }

        return DB::query()
            ->fromSub($profiles->groupByRaw(implode(', ', $groupBy)), 'logbook_accounts')
            ->count();
    }

    /**
     * @return Collection<int, array{name: string, date: string, purpose: string, classification: string, biometric: bool}>
     */
    private function latestLogbookTransactions(): Collection
    {
        if (Schema::hasTable('logbook')) {
            $categoryColumn = collect(['person_category', 'person_classification'])
                ->first(fn (string $column): bool => Schema::hasColumn('logbook', $column));
            $rows = DB::table('logbook')
                ->orderByDesc('time_in')
                ->limit(6)
                ->get();

            return $rows->map(fn (object $row): array => [
                'name' => trim((string) ($row->full_name ?? '')) ?: 'Unknown visitor',
                'date' => isset($row->time_in) && $row->time_in
                    ? Carbon::parse($row->time_in)->format('M j, Y g:i A')
                    : '—',
                'purpose' => trim((string) ($row->purpose_of_transaction ?? '')) ?: 'Logbook transaction',
                'classification' => $categoryColumn !== null
                    ? ucfirst(strtolower(trim((string) ($row->{$categoryColumn} ?? ''))))
                    : 'Person',
                'biometric' => isset($row->fingerprint_id)
                    && trim((string) $row->fingerprint_id) !== ''
                    && (string) $row->fingerprint_id !== '0',
            ]);
        }

        if (! Schema::hasTable('logbook_entries')) {
            return collect();
        }

        return LogbookEntry::query()
            ->with('user')
            ->orderByDesc('entry_at')
            ->limit(6)
            ->get()
            ->map(function (LogbookEntry $entry): array {
                $name = trim((string) $entry->full_name);
                if ($name === '') {
                    $name = trim((string) ($entry->user?->name ?? '')) ?: 'Unknown visitor';
                }

                return [
                    'name' => $name,
                    'date' => $entry->entry_at?->format('M j, Y g:i A') ?? '—',
                    'purpose' => trim((string) ($entry->purpose_of_transaction ?: $entry->description)) ?: 'Logbook transaction',
                    'classification' => ucfirst(strtolower(trim((string) $entry->person_category))) ?: 'Person',
                    'biometric' => false,
                ];
            });
    }
}
