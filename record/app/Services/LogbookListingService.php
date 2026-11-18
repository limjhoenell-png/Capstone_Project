<?php

namespace App\Services;

use App\Models\LogbookEntry;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LogbookListingService
{
    /**
     * Optional classification, visitor, and authorization columns on legacy `logbook`.
     *
     * @param  list<string|\Illuminate\Database\Query\Expression>  $select
     * @return list<string|\Illuminate\Database\Query\Expression>
     */
    private function withLegacyVisitorColumns(array $select): array
    {
        if (! Schema::hasTable('logbook')) {
            return $select;
        }

        foreach ([
            'transaction_type',
            'is_authorized_representative',
            'authorization_letter_verified',
            'valid_id_verified',
            'represented_person_name',
            'status',
        ] as $col) {
            if (Schema::hasColumn('logbook', $col)) {
                $select[] = "logbook.{$col}";
            }
        }

        $personCategoryColumn = collect(['person_category', 'person_classification'])
            ->first(fn (string $column): bool => Schema::hasColumn('logbook', $column));
        if ($personCategoryColumn !== null) {
            $select[] = "logbook.{$personCategoryColumn} as person_category";
        }

        return $select;
    }

    /**
     * Admin / shared legacy listing query (no order, no pagination).
     */
    private function legacyAdminEntriesQuery(string $search, ?string $fromDate = null, ?string $toDate = null): QueryBuilder
    {
        // Read only from `logbook`. Optional join to `users` when schema supports it (many legacy DBs differ).
        $canMapUsers = Schema::hasTable('users')
            && Schema::hasColumn('users', 'employee_id')
            && Schema::hasColumn('logbook', 'fingerprint_id');

        $entries = DB::table('logbook');

        if ($fromDate !== null) {
            $entries->whereDate('logbook.time_in', '>=', $fromDate);
        }
        if ($toDate !== null) {
            $entries->whereDate('logbook.time_in', '<=', $toDate);
        }

        if ($canMapUsers) {
            $nameCol = Schema::hasColumn('users', 'name') ? 'users.name' : null;
            $fullNameCol = Schema::hasColumn('users', 'full_name') ? 'users.full_name' : null;

            $coalesce = 'logbook.full_name';
            if ($nameCol && $fullNameCol) {
                $coalesce = "COALESCE({$nameCol}, {$fullNameCol}, logbook.full_name)";
            } elseif ($nameCol) {
                $coalesce = "COALESCE({$nameCol}, logbook.full_name)";
            } elseif ($fullNameCol) {
                $coalesce = "COALESCE({$fullNameCol}, logbook.full_name)";
            }

            $entries
                ->leftJoin('users', 'users.employee_id', '=', 'logbook.fingerprint_id')
                ->select($this->withLegacyVisitorColumns([
                    'logbook.logbook_id',
                    'logbook.full_name',
                    'logbook.address',
                    'logbook.contact_number',
                    'logbook.purpose_of_transaction',
                    'logbook.fingerprint_id',
                    'logbook.time_in',
                    'users.id as mapped_user_id',
                    DB::raw("{$coalesce} as user_name"),
                ]));
        } else {
            $entries->select($this->withLegacyVisitorColumns([
                'logbook.logbook_id',
                'logbook.full_name',
                'logbook.address',
                'logbook.contact_number',
                'logbook.purpose_of_transaction',
                'logbook.fingerprint_id',
                'logbook.time_in',
                DB::raw('NULL as mapped_user_id'),
                DB::raw('logbook.full_name as user_name'),
            ]));
        }

        if ($search !== '') {
            $entries->where(function ($query) use ($search, $canMapUsers): void {
                $query
                    ->where('logbook.full_name', 'like', "%{$search}%")
                    ->orWhere('logbook.address', 'like', "%{$search}%")
                    ->orWhere('logbook.contact_number', 'like', "%{$search}%")
                    ->orWhere('logbook.purpose_of_transaction', 'like', "%{$search}%");

                foreach (['user_name', 'name'] as $nameColumn) {
                    if (Schema::hasColumn('logbook', $nameColumn)) {
                        $query->orWhere("logbook.{$nameColumn}", 'like', "%{$search}%");
                    }
                }

                $personCategoryColumn = collect(['person_category', 'person_classification'])
                    ->first(fn (string $column): bool => Schema::hasColumn('logbook', $column));
                if ($personCategoryColumn !== null) {
                    $query->orWhere("logbook.{$personCategoryColumn}", 'like', "%{$search}%");
                }

                if (Schema::hasColumn('logbook', 'transaction_type')) {
                    $query->orWhere('logbook.transaction_type', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('logbook', 'represented_person_name')) {
                    $query->orWhere('logbook.represented_person_name', 'like', "%{$search}%");
                }

                if ($canMapUsers) {
                    if (Schema::hasColumn('users', 'name')) {
                        $query->orWhere('users.name', 'like', "%{$search}%");
                    }
                    if (Schema::hasColumn('users', 'full_name')) {
                        $query->orWhere('users.full_name', 'like', "%{$search}%");
                    }
                }
            });
        }

        return $entries;
    }

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateLegacyEntries(string $search, int $perPage = 20, string $pageName = 'page', ?string $fromDate = null, ?string $toDate = null): LengthAwarePaginator
    {
        return $this        ->legacyAdminEntriesQuery($search, $fromDate, $toDate)
            ->orderByDesc('logbook.time_in')
            ->orderByDesc('logbook.logbook_id')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
    }

    /**
     * All legacy rows for the admin logbook table (scroll inside the card; same filters as pagination).
     *
     * @return Collection<int, object>
     */
    public function allLegacyEntries(string $search, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        return $this        ->legacyAdminEntriesQuery($search, $fromDate, $toDate)
            ->orderByDesc('logbook.time_in')
            ->orderByDesc('logbook.logbook_id')
            ->get();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, object>
     */
    public function legacyEntriesByIds(array $ids): Collection
    {
        return $this->legacyAdminEntriesQuery('', null, null)
            ->whereIn('logbook.logbook_id', $ids)
            ->orderByDesc('logbook.time_in')
            ->orderByDesc('logbook.logbook_id')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function legacyEntriesForPerson(
        string $name,
        string $classification,
        string $contactNumber,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): Collection
    {
        $categoryColumn = collect(['person_category', 'person_classification'])
            ->first(fn (string $column): bool => Schema::hasColumn('logbook', $column));
        $query = $this->legacyAdminEntriesQuery('', null, null)
            ->where('logbook.full_name', $name);

        if ($fromDate !== null) {
            $query->whereDate('logbook.time_in', '>=', $fromDate);
        }
        if ($toDate !== null) {
            $query->whereDate('logbook.time_in', '<=', $toDate);
        }
        if ($categoryColumn !== null) {
            $query->whereRaw("LOWER(TRIM(logbook.{$categoryColumn})) = ?", [strtolower($classification)]);
        }
        if (Schema::hasColumn('logbook', 'contact_number')) {
            $query->whereRaw("COALESCE(logbook.contact_number, '') = ?", [$contactNumber]);
        }

        return $query
            ->orderByDesc('logbook.time_in')
            ->orderByDesc('logbook.logbook_id')
            ->get();
    }

    /**
     * @return Builder<LogbookEntry>
     */
    private function appAdminEntriesQuery(string $search, ?string $fromDate = null, ?string $toDate = null): Builder
    {
        $entries = LogbookEntry::query()->with('user');
        if ($fromDate !== null) {
            $entries->whereDate('entry_at', '>=', $fromDate);
        }
        if ($toDate !== null) {
            $entries->whereDate('entry_at', '<=', $toDate);
        }

        if ($search !== '') {
            $entries->where(function ($query) use ($search): void {
                $query
                    ->where('description', 'like', "%{$search}%")
                    ->orWhere('purpose_of_transaction', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->when(Schema::hasColumn('logbook_entries', 'person_category'), function ($query) use ($search): void {
                        $query->orWhere('person_category', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%")
                            ->orWhere('contact_number', 'like', "%{$search}%");
                    });
            });
        }

        return $entries;
    }

    /**
     * @return LengthAwarePaginator<int, LogbookEntry>
     */
    public function paginateAppEntries(string $search, int $perPage = 20, string $pageName = 'page', ?string $fromDate = null, ?string $toDate = null): LengthAwarePaginator
    {
        return $this        ->appAdminEntriesQuery($search, $fromDate, $toDate)
            ->latest('entry_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
    }

    /**
     * All app logbook rows for the admin table (same filters as pagination).
     *
     * @return Collection<int, LogbookEntry>
     */
    public function allAppEntries(string $search, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        return $this        ->appAdminEntriesQuery($search, $fromDate, $toDate)
            ->latest('entry_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, LogbookEntry>
     */
    public function appEntriesByIds(array $ids): Collection
    {
        return LogbookEntry::query()
            ->with('user')
            ->whereIn('id', $ids)
            ->latest('entry_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, LogbookEntry>
     */
    public function appEntriesForPerson(
        string $name,
        string $classification,
        string $contactNumber,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): Collection
    {
        $query = LogbookEntry::query()
            ->with('user')
            ->where('full_name', $name);

        if ($fromDate !== null) {
            $query->whereDate('entry_at', '>=', $fromDate);
        }
        if ($toDate !== null) {
            $query->whereDate('entry_at', '<=', $toDate);
        }
        if (Schema::hasColumn('logbook_entries', 'person_category')) {
            $query->whereRaw('LOWER(TRIM(person_category)) = ?', [strtolower($classification)]);
        }
        if (Schema::hasColumn('logbook_entries', 'contact_number')) {
            $query->whereRaw("COALESCE(contact_number, '') = ?", [$contactNumber]);
        }

        return $query->latest('entry_at')->orderByDesc('id')->get();
    }

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateLegacyEntriesForEmployee(User $user, string $search, int $perPage = 20, string $pageName = 'page'): LengthAwarePaginator
    {
        $ids = $user->deviceMatchIds();
        $name = trim((string) ($user->name ?? ''));
        $fullName = trim((string) ($user->full_name ?? ''));

        $canMapUsers = Schema::hasTable('users')
            && Schema::hasColumn('users', 'employee_id')
            && Schema::hasColumn('logbook', 'fingerprint_id');

        $entries = DB::table('logbook');

        if ($canMapUsers) {
            $nameCol = Schema::hasColumn('users', 'name') ? 'users.name' : null;
            $fullNameCol = Schema::hasColumn('users', 'full_name') ? 'users.full_name' : null;
            $coalesce = 'logbook.full_name';
            if ($nameCol && $fullNameCol) {
                $coalesce = "COALESCE({$nameCol}, {$fullNameCol}, logbook.full_name)";
            } elseif ($nameCol) {
                $coalesce = "COALESCE({$nameCol}, logbook.full_name)";
            } elseif ($fullNameCol) {
                $coalesce = "COALESCE({$fullNameCol}, logbook.full_name)";
            }

            $entries
                ->leftJoin('users', 'users.employee_id', '=', 'logbook.fingerprint_id')
                ->select($this->withLegacyVisitorColumns([
                    'logbook.logbook_id',
                    'logbook.full_name',
                    'logbook.address',
                    'logbook.contact_number',
                    'logbook.purpose_of_transaction',
                    'logbook.fingerprint_id',
                    'logbook.time_in',
                    'users.id as mapped_user_id',
                    DB::raw("{$coalesce} as user_name"),
                ]));
        } else {
            $entries->select($this->withLegacyVisitorColumns([
                'logbook.logbook_id',
                'logbook.full_name',
                'logbook.address',
                'logbook.contact_number',
                'logbook.purpose_of_transaction',
                'logbook.fingerprint_id',
                'logbook.time_in',
                DB::raw('NULL as mapped_user_id'),
                DB::raw('logbook.full_name as user_name'),
            ]));
        }

        $entries->whereIn('logbook.fingerprint_id', $ids);

        if ($search !== '') {
            $entries->where(function ($query) use ($search, $canMapUsers): void {
                $query
                    ->where('logbook.full_name', 'like', "%{$search}%")
                    ->orWhere('logbook.address', 'like', "%{$search}%")
                    ->orWhere('logbook.contact_number', 'like', "%{$search}%")
                    ->orWhere('logbook.purpose_of_transaction', 'like', "%{$search}%");

                if (Schema::hasColumn('logbook', 'transaction_type')) {
                    $query->orWhere('logbook.transaction_type', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('logbook', 'represented_person_name')) {
                    $query->orWhere('logbook.represented_person_name', 'like', "%{$search}%");
                }

                if ($canMapUsers) {
                    if (Schema::hasColumn('users', 'name')) {
                        $query->orWhere('users.name', 'like', "%{$search}%");
                    }
                    if (Schema::hasColumn('users', 'full_name')) {
                        $query->orWhere('users.full_name', 'like', "%{$search}%");
                    }
                }
            });
        }

        return $entries
            ->orderByDesc('logbook.time_in')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, LogbookEntry>
     */
    public function paginateAppEntriesForEmployee(User $user, string $search, int $perPage = 20, string $pageName = 'page'): LengthAwarePaginator
    {
        $entries = LogbookEntry::query()
            ->with('user')
            ->where('user_id', $user->id);

        if ($search !== '') {
            $entries->where(function ($query) use ($search): void {
                $query
                    ->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%")
                            ->orWhere('contact_number', 'like', "%{$search}%");
                    });
            });
        }

        return $entries->latest('entry_at')->paginate($perPage, ['*'], $pageName)->withQueryString();
    }

    /**
     * @param  LengthAwarePaginator<int, object>|Collection<int, object>  $entries
     */
    public function legacyLiveSignature(LengthAwarePaginator|Collection $entries, string $search): string
    {
        $collection = $entries instanceof LengthAwarePaginator
            ? $entries->getCollection()
            : $entries->values();

        $total = $entries instanceof LengthAwarePaginator
            ? $entries->total()
            : $collection->count();

        $page = $entries instanceof LengthAwarePaginator
            ? $entries->currentPage()
            : 1;

        return sha1(json_encode([
            'mode' => 'legacy_logbook',
            'search' => $search,
            'total' => $total,
            'page' => $page,
            'per_page' => $entries instanceof LengthAwarePaginator ? $entries->perPage() : $collection->count(),
            'last_page' => $entries instanceof LengthAwarePaginator ? $entries->lastPage() : 1,
            'items' => $collection->map(function ($entry) {
                return [
                    'id' => $entry->logbook_id,
                    'full_name' => (string) $entry->full_name,
                    'address' => (string) $entry->address,
                    'contact_number' => (string) $entry->contact_number,
                    'purpose' => (string) $entry->purpose_of_transaction,
                    'fingerprint_id' => (string) $entry->fingerprint_id,
                    'time_in' => (string) $entry->time_in,
                    'mapped_user_id' => (int) ($entry->mapped_user_id ?? 0),
                    'transaction_type' => (string) data_get($entry, 'transaction_type', ''),
                    'person_category' => (string) data_get($entry, 'person_category', ''),
                    'is_authorized_representative' => (int) data_get($entry, 'is_authorized_representative', 0),
                    'authorization_letter_verified' => (int) data_get($entry, 'authorization_letter_verified', 0),
                    'valid_id_verified' => (int) data_get($entry, 'valid_id_verified', 0),
                    'represented_person_name' => (string) data_get($entry, 'represented_person_name', ''),
                ];
            })->all(),
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param  LengthAwarePaginator<int, LogbookEntry>|Collection<int, LogbookEntry>  $entries
     */
    public function appLiveSignature(LengthAwarePaginator|Collection $entries, string $search): string
    {
        $collection = $entries instanceof LengthAwarePaginator
            ? $entries->getCollection()
            : $entries->values();

        $total = $entries instanceof LengthAwarePaginator
            ? $entries->total()
            : $collection->count();

        $page = $entries instanceof LengthAwarePaginator
            ? $entries->currentPage()
            : 1;

        return sha1(json_encode([
            'mode' => 'logbook_entries',
            'search' => $search,
            'total' => $total,
            'page' => $page,
            'per_page' => $entries instanceof LengthAwarePaginator ? $entries->perPage() : $collection->count(),
            'last_page' => $entries instanceof LengthAwarePaginator ? $entries->lastPage() : 1,
            'items' => $collection->map(function (LogbookEntry $entry) {
                return [
                    'id' => $entry->id,
                    'user_id' => $entry->user_id,
                    'entry_at' => (string) $entry->entry_at,
                    'description' => (string) $entry->description,
                    'full_name' => (string) ($entry->full_name ?? ''),
                    'address' => (string) ($entry->address ?? ''),
                    'contact_number' => (string) ($entry->contact_number ?? ''),
                    'purpose_of_transaction' => (string) ($entry->purpose_of_transaction ?? ''),
                    'person_category' => (string) ($entry->person_category ?? ''),
                ];
            })->all(),
        ], JSON_UNESCAPED_UNICODE));
    }
}
