<?php

namespace App\Services;

use App\Models\LogbookEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LogbookAnalyticsService
{
    /**
     * @return array{
     *   top_visitor: array{name: string, count: int}|null,
     *   totals: array{records: int},
     *   by_day: list<array{label: string, count: int}>,
     *   by_week: list<array{label: string, count: int}>,
     *   by_month: list<array{label: string, count: int}>,
     *   by_year: list<array{label: string, count: int}>,
     *   purpose_breakdown: list<array{label: string, count: int}>,
     *   source: string
     * }
     */
    public function summary(): array
    {
        if (Schema::hasTable('logbook')) {
            return $this->legacySummary();
        }

        return $this->appEntriesSummary();
    }

    /**
     * @return array<string, mixed>
     */
    private function legacySummary(): array
    {
        $driver = DB::connection()->getDriverName();
        $dayExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', time_in)"
            : 'DATE(time_in)';

        $daily = DB::table('logbook')
            ->selectRaw("{$dayExpr} as day_bucket, COUNT(*) as c")
            ->groupBy('day_bucket')
            ->orderBy('day_bucket')
            ->get();

        $rollup = $this->rollupFromDaily($daily->map(fn ($r) => [
            'day' => (string) $r->day_bucket,
            'count' => (int) $r->c,
        ])->all());

        $top = DB::table('logbook')
            ->select('full_name')
            ->selectRaw('COUNT(*) as c')
            ->groupBy('full_name')
            ->orderByDesc('c')
            ->first();

        $purposeRows = DB::table('logbook')
            ->select('purpose_of_transaction as label')
            ->selectRaw('COUNT(*) as c')
            ->whereNotNull('purpose_of_transaction')
            ->where('purpose_of_transaction', '!=', '')
            ->groupBy('purpose_of_transaction')
            ->orderByDesc('c')
            ->limit(8)
            ->get();

        $total = (int) DB::table('logbook')->count();

        return [
            'source' => 'legacy_logbook',
            'totals' => ['records' => $total],
            'top_visitor' => $top ? [
                'name' => (string) $top->full_name,
                'count' => (int) $top->c,
            ] : null,
            'by_day' => $rollup['by_day'],
            'by_week' => $rollup['by_week'],
            'by_month' => $rollup['by_month'],
            'by_year' => $rollup['by_year'],
            'purpose_breakdown' => $purposeRows->map(fn ($r) => [
                'label' => mb_substr((string) $r->label, 0, 48),
                'count' => (int) $r->c,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appEntriesSummary(): array
    {
        $driver = DB::connection()->getDriverName();
        $dayExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', logbook_entries.entry_at)"
            : 'DATE(logbook_entries.entry_at)';

        $daily = DB::table('logbook_entries')
            ->selectRaw("{$dayExpr} as day_bucket, COUNT(*) as c")
            ->groupBy('day_bucket')
            ->orderBy('day_bucket')
            ->get();

        $rollup = $this->rollupFromDaily($daily->map(fn ($r) => [
            'day' => (string) $r->day_bucket,
            'count' => (int) $r->c,
        ])->all());

        $top = null;
        if (Schema::hasTable('users')) {
            $agg = DB::table('logbook_entries')
                ->select('user_id')
                ->selectRaw('COUNT(*) as c')
                ->groupBy('user_id')
                ->orderByDesc('c')
                ->first();

            if ($agg !== null) {
                $user = DB::table('users')->where('id', $agg->user_id)->first();
                $name = 'Visitor';
                if ($user !== null) {
                    $n = trim((string) ($user->name ?? ''));
                    $fn = Schema::hasColumn('users', 'full_name') ? trim((string) ($user->full_name ?? '')) : '';
                    $name = $n !== '' ? $n : ($fn !== '' ? $fn : 'Visitor #'.$agg->user_id);
                }
                $top = [
                    'name' => $name,
                    'count' => (int) $agg->c,
                ];
            }
        }

        $total = (int) LogbookEntry::query()->count();

        $purposeRows = LogbookEntry::query()
            ->select('description as label')
            ->selectRaw('COUNT(*) as c')
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->groupBy('description')
            ->orderByDesc('c')
            ->limit(8)
            ->get();

        return [
            'source' => 'logbook_entries',
            'totals' => ['records' => $total],
            'top_visitor' => $top,
            'by_day' => $rollup['by_day'],
            'by_week' => $rollup['by_week'],
            'by_month' => $rollup['by_month'],
            'by_year' => $rollup['by_year'],
            'purpose_breakdown' => $purposeRows->map(fn ($r) => [
                'label' => mb_substr((string) $r->label, 0, 48),
                'count' => (int) $r->c,
            ])->values()->all(),
        ];
    }

    /**
     * @param  list<array{day: string, count: int}>  $dailyRows
     * @return array{
     *   by_day: list<array{label: string, count: int}>,
     *   by_week: list<array{label: string, count: int}>,
     *   by_month: list<array{label: string, count: int}>,
     *   by_year: list<array{label: string, count: int}>
     * }
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
}
