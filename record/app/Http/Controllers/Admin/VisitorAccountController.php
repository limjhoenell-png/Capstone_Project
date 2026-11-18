<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitorAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VisitorAccountController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'classification' => ['nullable', 'in:all,Personnel,Visitor,Resident'],
            'search' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', 'in:5,10,15,20,25,50,75,100'],
        ]);
        $classification = $filters['classification'] ?? 'all';
        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');
        if (preg_match('/^.{0,1}$/us', $search) === 1) {
            $search = '';
        }
        $profileSource = $this->logbookProfilesQuery();
        $profileCounts = [
            'Personnel' => 0,
            'Visitor' => 0,
            'Resident' => 0,
        ];

        if ($profileSource !== null) {
            $profileCounts = DB::query()
                ->fromSub(clone $profileSource, 'profiles')
                ->select('person_category')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('person_category')
                ->pluck('total', 'person_category')
                ->map(fn ($total): int => (int) $total)
                ->all() + $profileCounts;

            $profiles = DB::query()
                ->fromSub(clone $profileSource, 'profiles')
                ->when($classification !== 'all', fn (QueryBuilder $query) => $query->where('person_category', $classification))
                ->when($search !== '', function (QueryBuilder $query) use ($search): void {
                    $query->where(function (QueryBuilder $matches) use ($search): void {
                        foreach (['full_name', 'person_category', 'address', 'contact_number'] as $column) {
                            $matches->orWhere($column, 'like', "%{$search}%");
                        }
                    });
                })
                ->orderBy('full_name')
                ->paginate($perPage, ['*'], 'profile_page')
                ->withQueryString();
        } else {
            $profiles = new LengthAwarePaginator(
                [],
                0,
                $perPage,
                LengthAwarePaginator::resolveCurrentPage('profile_page'),
                ['path' => $request->url(), 'pageName' => 'profile_page'],
            );
            $profiles->withQueryString();
        }

        return view('admin.visitors.index', [
            'title' => 'Logbook Accounts',
            'classification' => $classification,
            'profileCounts' => $profileCounts,
            'profiles' => $profiles,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function updateProfile(Request $request, int $profile): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'person_category' => ['required', 'in:Personnel,Visitor,Resident'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
        ]);
        $tableInfo = $this->profileTableInfo();

        if ($tableInfo === null) {
            abort(404);
        }

        [$table, $primaryKey, $categoryColumn] = $tableInfo;
        $record = DB::table($table)->where($primaryKey, $profile)->first();

        if ($record === null) {
            abort(404);
        }

        $oldName = (string) $record->full_name;
        $oldCategory = strtolower(trim((string) $record->{$categoryColumn}));
        $oldContact = property_exists($record, 'contact_number') ? (string) ($record->contact_number ?? '') : '';
        $updates = [
            'full_name' => $validated['full_name'],
            $categoryColumn => $validated['person_category'],
        ];

        if (Schema::hasColumn($table, 'address')) {
            $updates['address'] = $validated['address'] ?? '';
        }
        if (Schema::hasColumn($table, 'contact_number')) {
            $updates['contact_number'] = $validated['contact_number'] ?? '';
        }

        DB::table($table)
            ->whereRaw("LOWER(TRIM({$categoryColumn})) = ?", [$oldCategory])
            ->where('full_name', $oldName)
            ->when(Schema::hasColumn($table, 'contact_number'), function (QueryBuilder $query) use ($oldContact): void {
                $query->whereRaw("COALESCE(contact_number, '') = ?", [$oldContact]);
            })
            ->update($updates);

        return redirect()
            ->route('admin.visitors.index', ['classification' => $validated['person_category']])
            ->with('status', 'Logbook profile updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        VisitorAccount::query()->create($this->validated($request));

        return redirect()->route('admin.visitors.index')->with('status', 'Logbook account saved.');
    }

    public function update(Request $request, VisitorAccount $visitor): RedirectResponse
    {
        $visitor->update($this->validated($request));

        return redirect()->route('admin.visitors.index')->with('status', 'Logbook account updated.');
    }

    public function destroy(VisitorAccount $visitor): RedirectResponse
    {
        $visitor->delete();

        return redirect()->route('admin.visitors.index')->with('status', 'Logbook account removed.');
    }

    public function destroySelected(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile_ids' => ['required', 'array', 'min:1', 'max:100'],
            'profile_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $tableInfo = $this->profileTableInfo();

        if ($tableInfo === null) {
            abort(404);
        }

        [$table, $primaryKey, $categoryColumn] = $tableInfo;
        $contactExpression = Schema::hasColumn($table, 'contact_number')
            ? "COALESCE(contact_number, '')"
            : "''";
        $profileSource = $this->logbookProfilesQuery();

        if ($profileSource === null) {
            abort(404);
        }

        $count = DB::transaction(function () use ($profileSource, $validated, $table, $categoryColumn, $contactExpression): int {
            $selectedProfiles = DB::query()
                ->fromSub($profileSource, 'profiles')
                ->whereIn('profile_id', $validated['profile_ids'])
                ->get(['profile_id', 'full_name', 'person_category', 'contact_number']);

            if ($selectedProfiles->count() !== count($validated['profile_ids'])) {
                throw ValidationException::withMessages([
                    'profile_ids' => 'One or more selected profiles are no longer available.',
                ]);
            }

            foreach ($selectedProfiles as $profile) {
                DB::table($table)
                    ->whereRaw("LOWER(TRIM({$categoryColumn})) = ?", [strtolower((string) $profile->person_category)])
                    ->where('full_name', $profile->full_name)
                    ->whereRaw("{$contactExpression} = ?", [(string) $profile->contact_number])
                    ->delete();
            }

            return $selectedProfiles->count();
        });

        return redirect()
            ->route('admin.visitors.index')
            ->with('status', "Deleted {$count} logbook ".($count === 1 ? 'profile.' : 'profiles.'));
    }

    private function logbookProfilesQuery(): ?QueryBuilder
    {
        $tableInfo = $this->profileTableInfo();
        if ($tableInfo === null) {
            return null;
        }

        [$table, $primaryKey, $categoryColumn] = $tableInfo;
        $contactExpression = Schema::hasColumn($table, 'contact_number')
            ? "COALESCE(contact_number, '')"
            : "''";
        $addressSelect = Schema::hasColumn($table, 'address')
            ? 'MAX(address) as address'
            : "'' as address";
        $timeColumn = collect(['time_in', 'entry_at'])
            ->first(fn (string $column): bool => Schema::hasColumn($table, $column));
        $lastSeenSelect = $timeColumn !== null
            ? "MAX({$timeColumn}) as last_seen"
            : 'NULL as last_seen';
        $categoryExpression = "CASE LOWER(TRIM({$categoryColumn})) WHEN 'personnel' THEN 'Personnel' WHEN 'visitor' THEN 'Visitor' WHEN 'resident' THEN 'Resident' END";

        return DB::table($table)
            ->selectRaw("MIN({$primaryKey}) as profile_id")
            ->addSelect('full_name')
            ->selectRaw("{$categoryExpression} as person_category")
            ->selectRaw("{$contactExpression} as contact_number")
            ->selectRaw("{$addressSelect}")
            ->selectRaw('COUNT(*) as visit_count')
            ->selectRaw($lastSeenSelect)
            ->whereIn(DB::raw("LOWER(TRIM({$categoryColumn}))"), ['personnel', 'visitor', 'resident'])
            ->whereNotNull('full_name')
            ->whereRaw("TRIM(full_name) <> ''")
            ->groupByRaw("{$categoryExpression}, full_name, {$contactExpression}");
    }

    /**
     * @return array{string, string, string}|null
     */
    private function profileTableInfo(): ?array
    {
        $table = Schema::hasTable('logbook')
            ? 'logbook'
            : (Schema::hasTable('logbook_entries') ? 'logbook_entries' : null);

        if ($table === null || ! Schema::hasColumn($table, 'full_name')) {
            return null;
        }

        $primaryKey = $table === 'logbook'
            ? (Schema::hasColumn($table, 'logbook_id') ? 'logbook_id' : null)
            : (Schema::hasColumn($table, 'id') ? 'id' : null);
        $categoryColumn = $table === 'logbook'
            ? collect(['person_category', 'person_classification'])->first(fn (string $column): bool => Schema::hasColumn($table, $column))
            : (Schema::hasColumn($table, 'person_category') ? 'person_category' : null);

        if ($primaryKey === null || $categoryColumn === null) {
            return null;
        }

        return [$table, $primaryKey, $categoryColumn];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
        ]);
    }
}
