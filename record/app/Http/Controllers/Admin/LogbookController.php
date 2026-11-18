<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLogbookEntryRequest;
use App\Http\Requests\Admin\UpdateLogbookEntryRequest;
use App\Models\LogbookEntry;
use App\Models\User;
use App\Models\VisitorAccount;
use App\Services\LogbookListingService;
use App\Services\LogbookTransferService;
use App\Services\PhilippineLocationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LogbookController extends Controller
{
    public function __construct(
        private LogbookListingService $logbookListing,
        private LogbookTransferService $logbookTransfer,
    ) {}

    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $search = $this->normalizedListingSearch($request);

        if ($request->wantsJson()) {
            return $this->indexJson($search, $request);
        }

        return redirect()->route('admin.barangay-logbook.logs', $request->query());
    }

    /**
     * Full admin logbook listing UI (search, table, import/export, live sync).
     *
     * @param  array<string, mixed>  $viewExtras  Optional keys: logbooksListUrl, pageTitle
     */
    public function renderIndexHtml(Request $request, array $viewExtras = []): View
    {
        $search = $this->normalizedListingSearch($request);
        $perPage = $this->listingPageSize($request);
        [$fromDate, $toDate] = $this->dateFilters($request);

        if (Schema::hasTable('logbook')) {
            $entries = $this->logbookListing->paginateLegacyEntries($search, $perPage, 'page', $fromDate, $toDate);

            return view('admin.logbooks.index', array_merge([
                'legacyEntries' => $entries,
                'search' => $search,
                'perPage' => $perPage,
                'perPageOptions' => [10, 15, 25, 50],
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'realtime' => $request->boolean('realtime', true),
                'liveSignature' => $this->logbookListing->legacyLiveSignature($entries, $search),
            ], $viewExtras));
        }

        $entries = $this->logbookListing->paginateAppEntries($search, $perPage, 'page', $fromDate, $toDate);

        return view('admin.logbooks.index', array_merge([
            'entries' => $entries,
            'search' => $search,
            'perPage' => $perPage,
            'perPageOptions' => [10, 15, 25, 50],
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'realtime' => $request->boolean('realtime', true),
            'liveSignature' => $this->logbookListing->appLiveSignature($entries, $search),
        ], $viewExtras));
    }

    private function listingPageSize(Request $request): int
    {
        $perPage = $request->integer('per_page', 10);

        return in_array($perPage, [10, 15, 25, 50], true) ? $perPage : 10;
    }

    private function normalizedListingSearch(Request $request): string
    {
        $search = trim($request->string('search')->toString());

        return Str::length($search) >= 1 ? $search : '';
    }

    private function dateFilters(Request $request): array
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $from = $validated['from_date'] ?? null;
        $to = $validated['to_date'] ?? null;

        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function indexJson(string $search, Request $request): JsonResponse
    {
        $search = Str::length(trim($search)) >= 1 ? trim($search) : '';
        $perPage = (int) min(100, max(5, $request->integer('per_page', 10)));

        if (Schema::hasTable('logbook')) {
            $entries = $this->logbookListing->paginateLegacyEntries($search, $perPage);

            return response()->json($this->logbookJsonPayload($entries, fn ($entry) => [
                'id' => (string) $entry->logbook_id,
                'full_name' => (string) ($entry->user_name ?? $entry->full_name ?? ''),
                'address' => (string) ($entry->address ?? ''),
                'contact_number' => (string) ($entry->contact_number ?? ''),
                'purpose_of_transaction' => (string) ($entry->purpose_of_transaction ?? ''),
                'purpose' => (string) ($entry->purpose_of_transaction ?? ''),
                'person_category' => Str::title((string) ($entry->person_category ?? '')),
                'visited_at' => (string) ($entry->time_in ?? ''),
                'source' => 'legacy',
            ]));
        }

        $entries = $this->logbookListing->paginateAppEntries($search, $perPage);

        return response()->json($this->logbookJsonPayload($entries, function ($entry) {
            /** @var LogbookEntry $entry */
            $user = $entry->user;
            $name = trim((string) ($entry->full_name ?? ''));
            if ($name === '' && $user !== null) {
                $name = trim((string) ($user->name ?? ''));
            }
            if ($name === '' && $user !== null) {
                $name = trim((string) ($user->full_name ?? ''));
            }
            if ($name === '' && $entry->user_id !== null) {
                $name = 'User #'.$entry->user_id;
            }

            return [
                'id' => (string) $entry->id,
                'full_name' => $name,
                'address' => (string) ($entry->address ?? ''),
                'contact_number' => (string) ($entry->contact_number ?? ''),
                'purpose_of_transaction' => (string) ($entry->purpose_of_transaction ?? ($entry->description === 'Imported logbook entry' ? '' : ($entry->description ?? ''))),
                'purpose' => (string) ($entry->purpose_of_transaction ?? ($entry->description === 'Imported logbook entry' ? '' : ($entry->description ?? ''))),
                'person_category' => Str::title((string) ($entry->person_category ?? '')),
                'visited_at' => $entry->entry_at?->toIso8601String() ?? '',
                'source' => 'app',
            ];
        }));
    }

    /**
     * @template T
     *
     * @param  LengthAwarePaginator<int, T>  $entries
     * @param  callable(T): array<string, string>  $mapper
     */
    private function logbookJsonPayload(LengthAwarePaginator $entries, callable $mapper): array
    {
        $data = collect($entries->items())->map($mapper)->values()->all();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
                'from' => $entries->firstItem(),
                'to' => $entries->lastItem(),
            ],
            'links' => [
                'first' => $entries->url(1),
                'last' => $entries->url($entries->lastPage()),
                'prev' => $entries->previousPageUrl(),
                'next' => $entries->nextPageUrl(),
            ],
        ];
    }

    public function liveSync(Request $request): JsonResponse
    {
        $search = $this->normalizedListingSearch($request);
        $perPage = $this->listingPageSize($request);
        [$fromDate, $toDate] = $this->dateFilters($request);

        try {
            if (Schema::hasTable('logbook')) {
                $entries = $this->logbookListing->paginateLegacyEntries($search, $perPage, 'page', $fromDate, $toDate);
                $signature = $this->logbookListing->legacyLiveSignature($entries, $search);

                if ($request->query('signature') === $signature) {
                    return response()->json([
                        'changed' => false,
                        'signature' => $signature,
                    ]);
                }

                return response()->json([
                    'changed' => true,
                    'signature' => $signature,
                    'html' => view('admin.logbooks.partials.live-region', [
                        'legacyEntries' => $entries,
                        'perPage' => $perPage,
                        'perPageOptions' => [10, 15, 25, 50],
                        'fromDate' => $fromDate,
                        'toDate' => $toDate,
                    ])->render(),
                ]);
            }

            $entries = $this->logbookListing->paginateAppEntries($search, $perPage, 'page', $fromDate, $toDate);
            $signature = $this->logbookListing->appLiveSignature($entries, $search);

            if ($request->query('signature') === $signature) {
                return response()->json([
                    'changed' => false,
                    'signature' => $signature,
                ]);
            }

            return response()->json([
                'changed' => true,
                'signature' => $signature,
                'html' => view('admin.logbooks.partials.live-region', [
                    'entries' => $entries,
                    'perPage' => $perPage,
                    'perPageOptions' => [10, 15, 25, 50],
                    'fromDate' => $fromDate,
                    'toDate' => $toDate,
                ])->render(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'changed' => false,
                'error' => 'Unable to sync logbook data.',
            ], 500);
        }
    }

    public function printRecords(Request $request): View
    {
        $validated = $request->validate([
            'selected_ids' => ['nullable', 'array', 'max:100'],
            'selected_ids.*' => ['required', 'integer', 'distinct'],
            'name' => ['nullable', 'string', 'max:150'],
            'classification' => ['nullable', 'string', 'max:30'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:255'],
            'period' => ['nullable', 'in:all,day,week,month,custom'],
            'date' => ['required_if:period,day', 'nullable', 'date_format:Y-m-d'],
            'month' => ['required_if:period,month', 'nullable', 'date_format:Y-m'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $name = trim((string) ($validated['name'] ?? ''));
        $classification = trim((string) ($validated['classification'] ?? ''));
        $contactNumber = trim((string) ($validated['contact_number'] ?? ''));
        $selectedIds = $validated['selected_ids'] ?? [];
        $search = trim((string) ($validated['search'] ?? ''));
        $period = $validated['period'] ?? null;
        $periodLabel = null;
        if ($name !== '' && $period !== null) {
            if ($period === 'all') {
                $fromDate = null;
                $toDate = null;
                $periodLabel = 'All Records';
            } elseif ($period === 'day') {
                $fromDate = $validated['date'];
                $toDate = $validated['date'];
                $periodLabel = Carbon::parse($fromDate)->format('F j, Y');
            } elseif ($period === 'week') {
                $week = now();
                $fromDate = $week->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
                $toDate = $week->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
                $periodLabel = 'This Week ('.Carbon::parse($fromDate)->format('M d').' – '.Carbon::parse($toDate)->format('M d, Y').')';
            } elseif ($period === 'month') {
                $month = Carbon::createFromFormat('!Y-m', $validated['month']);
                $fromDate = $month->copy()->startOfMonth()->format('Y-m-d');
                $toDate = $month->copy()->endOfMonth()->format('Y-m-d');
                $periodLabel = $month->format('F Y');
            } else {
                $customDates = $request->validate([
                    'from_date' => ['required', 'date_format:Y-m-d'],
                    'to_date' => ['required', 'date_format:Y-m-d'],
                ]);
                $fromDate = $customDates['from_date'];
                $toDate = $customDates['to_date'];
                if ($fromDate > $toDate) {
                    [$fromDate, $toDate] = [$toDate, $fromDate];
                }
                $from = Carbon::parse($fromDate);
                $to = Carbon::parse($toDate);
                $periodLabel = $from->format($from->year === $to->year ? 'M d' : 'M d, Y').' – '.$to->format('M d, Y');
            }
        } else {
            [$fromDate, $toDate] = $this->dateFilters($request);
        }
        $isLegacy = Schema::hasTable('logbook');

        if ($selectedIds !== []) {
            $entries = $isLegacy
                ? $this->logbookListing->legacyEntriesByIds($selectedIds)
                : $this->logbookListing->appEntriesByIds($selectedIds);
            $resolvedIds = $isLegacy
                ? $entries->pluck('logbook_id')->unique()->count()
                : $entries->pluck('id')->unique()->count();
            abort_if($resolvedIds !== count($selectedIds), 404, 'One or more selected logbook records are no longer available.');
        } elseif ($name !== '') {
            abort_if($classification === '', 422, 'A classification is required to print this profile.');
            $entries = $isLegacy
                ? $this->logbookListing->legacyEntriesForPerson($name, $classification, $contactNumber, $fromDate, $toDate)
                : $this->logbookListing->appEntriesForPerson($name, $classification, $contactNumber, $fromDate, $toDate);
        } else {
            abort_if($search === '', 422, 'Search for a person or select logbook records to print.');
            $entries = $isLegacy
                ? $this->logbookListing->allLegacyEntries($search, $fromDate, $toDate)
                : $this->logbookListing->allAppEntries($search, $fromDate, $toDate);
        }

        abort_if($entries->isEmpty(), 404, 'No matching logbook records were found.');

        if ($periodLabel === null) {
            $periodLabel = $selectedIds !== []
                ? 'Selected Records'
                : (($fromDate || $toDate)
                    ? (($fromDate ?: 'Beginning').' to '.($toDate ?: 'Present'))
                    : 'All Records');
        }

        $records = $entries->map(function ($entry) use ($isLegacy): array {
            $user = $isLegacy ? null : $entry->user;
            $timestamp = $isLegacy ? ($entry->time_in ?? null) : $entry->entry_at;
            $purpose = $isLegacy
                ? ($entry->purpose_of_transaction ?? '')
                : ($entry->purpose_of_transaction ?: ($entry->description === 'Imported logbook entry' ? '' : $entry->description));

            return [
                'name' => (string) ($isLegacy
                    ? ($entry->user_name ?: $entry->full_name)
                    : ($entry->full_name ?: ($user?->name ?? $user?->full_name ?? ''))),
                'classification' => Str::title((string) data_get($entry, 'person_category', '')),
                'address' => (string) ($entry->address ?? ''),
                'contact_number' => (string) ($entry->contact_number ?: ($user?->contact_number ?? '')),
                'purpose' => (string) $purpose,
                'timestamp' => $timestamp === null ? '' : Carbon::parse($timestamp)->format('Y-m-d h:i:s A'),
                'date' => $timestamp === null ? null : Carbon::parse($timestamp)->format('Y-m-d'),
                'status' => trim((string) data_get($entry, 'status', '')) ?: 'Recorded',
            ];
        });

        $groups = $records
            ->groupBy(fn (array $record): string => json_encode([
                $record['name'],
                $record['classification'],
                $record['contact_number'],
            ], JSON_UNESCAPED_UNICODE))
            ->map(function ($personRecords) use ($fromDate, $toDate): array {
                $first = $personRecords->first();
                $dates = $personRecords->pluck('date')->filter()->sort()->values();

                return [
                    'name' => $first['name'],
                    'classification' => $first['classification'],
                    'address' => $first['address'],
                    'contact_number' => $first['contact_number'],
                    'date_range' => $fromDate || $toDate
                        ? (($fromDate ?: 'Beginning'). ' to '.($toDate ?: 'Present'))
                        : ($dates->isEmpty() ? '—' : ($dates->first() === $dates->last()
                            ? $dates->first()
                            : $dates->first().' to '.$dates->last())),
                    'entries' => $personRecords->values()->all(),
                ];
            })
            ->values()
            ->all();

        return view('admin.logbooks.print', [
            'groups' => $groups,
            'periodLabel' => $periodLabel,
            'printedAt' => now()->format('Y-m-d h:i A'),
        ]);
    }

    public function downloadFormat()
    {
        $filePath = $this->logbookTransfer->templateXlsxFile();

        return response()->download($filePath, 'logbook_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => ['required', 'in:csv,xlsx,pdf'],
            'mode' => ['required', 'in:custom,day,month,year,person,all'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'search' => ['nullable', 'string', 'max:255'],
            'person' => ['nullable', 'string', 'max:255'],
        ]);

        $format = $validated['format'];
        $mode = $validated['mode'];
        $search = trim((string) ($validated['search'] ?? ''));
        $fromDate = null;
        $toDate = null;

        if ($mode === 'all') {
            $fromDate = $validated['from_date'] ?? null;
            $toDate = $validated['to_date'] ?? null;
            if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
                [$fromDate, $toDate] = [$toDate, $fromDate];
            }
        } elseif ($mode === 'custom') {
            $custom = $request->validate([
                'from_date' => ['required', 'date_format:Y-m-d'],
                'to_date' => ['required', 'date_format:Y-m-d'],
            ]);
            $fromDate = $custom['from_date'];
            $toDate = $custom['to_date'];
            if ($fromDate > $toDate) {
                [$fromDate, $toDate] = [$toDate, $fromDate];
            }
        } elseif ($mode === 'day') {
            $fromDate = now()->format('Y-m-d');
            $toDate = $fromDate;
        } elseif ($mode === 'month') {
            $month = (int) ($validated['month'] ?? now()->month);
            $year = (int) ($validated['year'] ?? now()->year);
            $start = Carbon::create($year, $month, 1)->startOfDay();
            $fromDate = $start->format('Y-m-d');
            $toDate = $start->copy()->endOfMonth()->format('Y-m-d');
        } elseif ($mode === 'year') {
            $year = (int) ($validated['year'] ?? now()->year);
            $fromDate = "{$year}-01-01";
            $toDate = "{$year}-12-31";
        } elseif ($mode === 'person') {
            $search = trim((string) ($validated['person'] ?? $search));
        }

        $flagLabel = static function (mixed $value): string {
            $normalized = strtolower(trim((string) $value));

            return in_array($normalized, ['1', 'true', 'yes'], true) ? 'Yes' : 'No';
        };
        $formatTime = static function (mixed $value): string {
            return $value === null || trim((string) $value) === ''
                ? ''
                : Carbon::parse($value)->format('Y-m-d h:i:s A');
        };

        if (Schema::hasTable('logbook')) {
            $records = $this->logbookListing->allLegacyEntries($search, $fromDate, $toDate)->map(function ($entry) use ($flagLabel, $formatTime): array {
                $isRepresentative = $flagLabel(data_get($entry, 'is_authorized_representative')) === 'Yes';

                return [
                    (string) ($entry->logbook_id ?? ''),
                    (string) ($entry->user_name ?? $entry->full_name ?? ''),
                    Str::title((string) ($entry->person_category ?? '')),
                    (string) ($entry->address ?? ''),
                    (string) ($entry->contact_number ?? ''),
                    (string) ($entry->purpose_of_transaction ?? ''),
                    $flagLabel(data_get($entry, 'is_authorized_representative')),
                    $flagLabel(data_get($entry, 'authorization_letter_verified')),
                    $flagLabel(data_get($entry, 'valid_id_verified')),
                    $isRepresentative ? trim((string) data_get($entry, 'represented_person_name', '')) : '',
                    $formatTime($entry->time_in ?? null),
                    (string) data_get($entry, 'fingerprint_id', ''),
                ];
            })->all();
        } else {
            $records = $this->logbookListing->allAppEntries($search, $fromDate, $toDate)->map(function (LogbookEntry $entry) use ($flagLabel, $formatTime): array {
                $user = $entry->user;
                $isRepresentative = $flagLabel($entry->is_authorized_representative) === 'Yes';

                return [
                    (string) $entry->id,
                    (string) ($entry->full_name ?: ($user?->name ?? $user?->full_name ?? '')),
                    Str::title((string) ($entry->person_category ?? '')),
                    (string) ($entry->address ?? ''),
                    (string) ($entry->contact_number ?: ($user?->contact_number ?? '')),
                    (string) ($entry->purpose_of_transaction ?: $entry->description),
                    $flagLabel($entry->is_authorized_representative),
                    $flagLabel($entry->authorization_letter_verified),
                    $flagLabel($entry->valid_id_verified),
                    $isRepresentative ? trim((string) ($entry->represented_person_name ?? '')) : '',
                    $formatTime($entry->entry_at),
                    '',
                ];
            })->all();
        }

        $filename = 'logbook_export_'.now()->format('Ymd_His');
        if ($format === 'xlsx') {
            $filePath = $this->logbookTransfer->exportXlsxFile($records);

            return response()->download($filePath, $filename.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        $headers = [
            'ID', 'User', 'Person Classification', 'Address', 'Contact', 'Purpose of Transaction',
            'Rep', 'Letter', 'ID', 'Representing', 'Time In', 'Fingerprint ID',
        ];

        if ($format === 'csv') {
            $stream = fopen('php://temp', 'r+');
            fputcsv($stream, $headers);
            foreach ($records as $record) {
                fputcsv($stream, $record);
            }
            rewind($stream);
            $content = stream_get_contents($stream);
            fclose($stream);

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ]);
        }

        $pdf = Pdf::loadView('admin.logbooks.pdf', [
            'headers' => $headers,
            'records' => $records,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['nullable', 'file', 'mimes:csv,txt,xlsx'],
            'rows' => ['nullable', 'string'],
        ]);

        try {
            $rows = [];
            $file = $request->file('file');
            $rowsPayload = $validated['rows'] ?? null;

            if ($rowsPayload !== null && $rowsPayload !== '') {
                $decoded = json_decode($rowsPayload, true);
                if (! is_array($decoded)) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Invalid Excel payload. Please try again.',
                    ], 422);
                }

                $rows = $decoded;
            } elseif ($file !== null) {
                if (strtolower($file->getClientOriginalExtension()) === 'xlsx') {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Direct .xlsx upload is not supported on this server. The page parser will handle .xlsx automatically; please retry the same file upload.',
                    ], 422);
                }

                $rows = $this->logbookTransfer->parseCsvUpload($file);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'Please choose a .csv or .xlsx file first.',
                ], 422);
            }

            $result = $this->logbookTransfer->importRows($rows);

            return response()->json([
                'ok' => true,
                'message' => sprintf(
                    'Import complete: %d inserted, %d skipped duplicates/empty, %d failed.',
                    $result['inserted'],
                    $result['skipped'],
                    $result['errors'],
                ),
                'result' => $result,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'Import failed. Please check file format and try again.',
            ], 500);
        }
    }

    public function create(): View
    {
        return view('admin.logbooks.create', [
            'purposeOptions' => $this->logbookTransfer->purposeOptions(),
            'contactCountryCodes' => $this->logbookTransfer->contactCountryCodes(),
        ]);
    }

    public function store(StoreLogbookEntryRequest $request): RedirectResponse
    {
        $validated = $this->validateManualEntry($request);

        if (Schema::hasTable('logbook')) {
            if (! Schema::hasColumn('logbook', 'person_category')
                && ! Schema::hasColumn('logbook', 'person_classification')) {
                throw ValidationException::withMessages([
                    'person_category' => 'The shared logbook table has no person classification column, so this entry cannot be saved safely.',
                ]);
            }

            if (($validated['transaction_type'] ?? null) === 'Claim Documents On Behalf') {
                $claimColumns = [
                    'transaction_type',
                    'is_authorized_representative',
                    'authorization_letter_verified',
                    'valid_id_verified',
                    'represented_person_name',
                    'represented_person_classification',
                    'additional_notes',
                ];
                $missingClaimColumns = array_values(array_filter(
                    $claimColumns,
                    static fn (string $column): bool => ! Schema::hasColumn('logbook', $column),
                ));
                if ($missingClaimColumns !== []) {
                    throw ValidationException::withMessages([
                        'transaction_type' => 'The shared logbook table is missing document-claim fields. Run the pending document claim migration before saving claims.',
                    ]);
                }
            }

            DB::table('logbook')->insert($this->legacyRow($validated, null));

            return redirect()->route('admin.barangay-logbook.logs')->with('status', 'Logbook entry created.');
        }

        $requiredColumns = [
            'user_id',
            'entry_at',
            'description',
            'person_category',
            'full_name',
            'address',
            'contact_number',
            'purpose_of_transaction',
        ];
        $missingColumns = array_values(array_filter(
            $requiredColumns,
            static fn (string $column): bool => ! Schema::hasColumn('logbook_entries', $column),
        ));
        if ($missingColumns !== []) {
            throw ValidationException::withMessages([
                'person_category' => 'The logbook entries table is missing required fields. Run the pending database migrations before saving manual entries.',
            ]);
        }
        if (($validated['transaction_type'] ?? null) === 'Claim Documents On Behalf') {
            $claimColumns = [
                'transaction_type',
                'is_authorized_representative',
                'authorization_letter_verified',
                'valid_id_verified',
                'represented_person_name',
                'represented_person_classification',
                'additional_notes',
            ];
            $missingClaimColumns = array_values(array_filter(
                $claimColumns,
                static fn (string $column): bool => ! Schema::hasColumn('logbook_entries', $column),
            ));
            if ($missingClaimColumns !== []) {
                throw ValidationException::withMessages([
                    'transaction_type' => 'The logbook entries table is missing document-claim fields. Run the pending document claim migration before saving claims.',
                ]);
            }
        }
        $userColumn = collect(Schema::getColumns('logbook_entries'))
            ->first(static fn (array $column): bool => $column['name'] === 'user_id');
        if ($userColumn !== null && ! $userColumn['nullable']) {
            throw ValidationException::withMessages([
                'person_category' => 'The logbook entries table still requires a linked user. Run the pending manual-entry migration before saving entries.',
            ]);
        }

        $entryData = [
            'user_id' => null,
            'entry_at' => $validated['time_in'],
            'description' => $validated['purpose_of_transaction'],
            'person_category' => $validated['person_category'],
            'full_name' => $validated['full_name'],
            'address' => $validated['address'],
            'contact_number' => $validated['contact_number'],
            'purpose_of_transaction' => $validated['purpose_of_transaction'],
        ];
        foreach ([
            'transaction_type',
            'is_authorized_representative',
            'authorization_letter_verified',
            'valid_id_verified',
            'represented_person_name',
            'represented_person_classification',
            'additional_notes',
        ] as $column) {
            if (array_key_exists($column, $validated) && $validated[$column] !== null) {
                $entryData[$column] = $validated[$column];
            }
        }
        LogbookEntry::query()->create($entryData);

        return redirect()->route('admin.barangay-logbook.logs')->with('status', 'Logbook entry created.');
    }

    public function edit(string $logbook): View
    {
        if (Schema::hasTable('logbook')) {
            $entry = DB::table('logbook')->where('logbook_id', $logbook)->first();
            abort_if($entry === null, 404);

            return view('admin.logbooks.edit', [
                'legacyEntry' => $entry,
                'employees' => User::query()->orderBy('name')->get(),
                'visitors' => $this->visitors(),
                'purposeOptions' => $this->logbookTransfer->purposeOptions(),
                'useLegacyLogbook' => true,
            ]);
        }

        $model = LogbookEntry::query()->findOrFail($logbook);

        return view('admin.logbooks.edit', [
            'entry' => $model,
            'employees' => User::query()->orderBy('name')->get(),
            'visitors' => collect(),
            'purposeOptions' => $this->logbookTransfer->purposeOptions(),
            'useLegacyLogbook' => false,
        ]);
    }

    public function update(Request $request, string $logbook): RedirectResponse
    {
        if (Schema::hasTable('logbook')) {
            $validated = $this->validateLegacyEntry($request);

            $user = null;
            if (! empty($validated['user_id'])) {
                $user = User::query()->find($validated['user_id']);
            }

            DB::table('logbook')
                ->where('logbook_id', $logbook)
                ->update($this->legacyRow($validated, $user, false));

            return redirect()->route('admin.barangay-logbook.logs')->with('status', 'Logbook entry updated.');
        }

        $model = LogbookEntry::query()->findOrFail($logbook);
        $validated = $request->validate((new UpdateLogbookEntryRequest)->rules());
        $validated['contact_number'] = $this->validatedContactNumber($request);
        $validated['description'] = $validated['purpose_of_transaction'];
        $model->update($validated);

        return redirect()->route('admin.barangay-logbook.logs')->with('status', 'Logbook entry updated.');
    }

    public function destroy(string $logbook): RedirectResponse
    {
        if (Schema::hasTable('logbook')) {
            DB::table('logbook')->where('logbook_id', $logbook)->delete();

            return redirect()->route('admin.barangay-logbook.logs')->with('status', 'Logbook entry deleted.');
        }

        $model = LogbookEntry::query()->findOrFail($logbook);
        $model->delete();

        return redirect()->route('admin.barangay-logbook.logs')->with('status', 'Logbook entry deleted.');
    }

    public function destroySelected(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $ids = $validated['selected_ids'];

        if (Schema::hasTable('logbook')) {
            $deleted = DB::table('logbook')->whereIn('logbook_id', $ids)->delete();
        } else {
            $deleted = LogbookEntry::query()->whereKey($ids)->delete();
        }

        return redirect()
            ->route('admin.barangay-logbook.logs')
            ->with('status', "Deleted {$deleted} logbook ".($deleted === 1 ? 'entry.' : 'entries.'));
    }

    public function destroyUser(Request $request, string $logbook): RedirectResponse
    {
        if (! Schema::hasTable('logbook')) {
            return redirect()->route('admin.barangay-logbook.logs')->withErrors(['error' => 'Legacy logbook table not available.']);
        }

        $row = DB::table('logbook')->where('logbook_id', $logbook)->first();
        if ($row === null) {
            return redirect()->route('admin.barangay-logbook.logs')->withErrors(['error' => 'Logbook record not found.']);
        }

        if ($row->fingerprint_id === null || $row->fingerprint_id === '' || (string) $row->fingerprint_id === '0') {
            DB::table('logbook')->where('logbook_id', $row->logbook_id)->delete();

            return redirect()->route('admin.barangay-logbook.logs')->with('status', 'Logbook entry deleted. No employee account was linked.');
        }

        $user = User::query()->where('employee_id', (string) $row->fingerprint_id)->first();
        if ($user === null) {
            DB::table('logbook')->where('fingerprint_id', (string) $row->fingerprint_id)->delete();

            return redirect()->route('admin.barangay-logbook.logs')->with('status', 'Logbook rows for that device id were deleted. No matching account was found.');
        }

        $request->validate([
            'confirm_name' => ['required', 'string', 'in:'.$user->name],
        ]);

        DB::transaction(function () use ($row, $user): void {
            DB::table('logbook')->where('fingerprint_id', (string) $row->fingerprint_id)->delete();

            if (Schema::hasTable('logbook_entries')) {
                DB::table('logbook_entries')->where('user_id', $user->id)->delete();
            }

            if (Schema::hasTable('attendances')) {
                DB::table('attendances')->where('user_id', $user->id)->delete();
            }

            if (Auth::id() !== $user->id) {
                $user->delete();
            }
        });

        return redirect()->route('admin.barangay-logbook.logs')->with('status', 'Deleted account '.$user->name.' and records linked by device id.');
    }

    private function visitors()
    {
        if (! Schema::hasTable('visitor_accounts')) {
            return collect();
        }

        return VisitorAccount::query()->orderBy('full_name')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLegacyEntry(Request $request): array
    {
        $formattedContact = $this->validatedContactNumber($request);
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'visitor_account_id' => ['nullable', 'integer'],
            'full_name' => ['nullable', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
            'purpose_of_transaction' => ['required', 'string'],
            'fingerprint_id' => ['nullable', 'string', 'max:64'],
            'time_in' => ['required', 'date'],
            'transaction_type' => ['nullable', 'string', 'max:100'],
            'is_authorized_representative' => ['nullable', 'boolean'],
            'authorization_letter_verified' => ['nullable', 'boolean'],
            'valid_id_verified' => ['nullable', 'boolean'],
            'represented_person_name' => ['nullable', 'string', 'max:150'],
        ]);

        $validated['contact_number'] = $formattedContact;

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateManualEntry(StoreLogbookEntryRequest $request): array
    {
        $validated = $request->validated();
        $isDocumentClaim = $validated['transaction_type'] === 'claim_documents_on_behalf';

        if ($isDocumentClaim) {
            $validated['purpose_of_transaction'] = $validated['claim_document'] === 'Other'
                ? trim((string) $validated['claim_document_other'])
                : $validated['claim_document'];
            $validated['transaction_type'] = 'Claim Documents On Behalf';
            $validated['is_authorized_representative'] = true;
            $validated['represented_person_name'] = implode(' ', array_filter([
                trim((string) $validated['document_owner_first_name']),
                trim((string) ($validated['document_owner_middle_name'] ?? '')),
                trim((string) $validated['document_owner_last_name']),
                trim((string) ($validated['document_owner_name_extension'] ?? '')),
            ], static fn (string $part): bool => $part !== ''));
            $validated['represented_person_name'] = validator(
                ['represented_person_name' => $validated['represented_person_name']],
                ['represented_person_name' => ['required', 'string', 'max:150']],
            )->validate()['represented_person_name'];
            $validated['additional_notes'] = trim((string) ($validated['additional_notes'] ?? ''));
        } else {
            if ($validated['purpose_choice'] === 'Other') {
                $validated['purpose_of_transaction'] = trim((string) $validated['purpose_other']);
            } else {
                $purposeOptions = $this->logbookTransfer->purposeOptions();
                if (! in_array($validated['purpose_choice'], $purposeOptions, true)) {
                    throw ValidationException::withMessages([
                        'purpose_choice' => 'Choose a valid purpose of visit.',
                    ]);
                }
                $validated['purpose_of_transaction'] = $validated['purpose_choice'];
            }
            $validated['transaction_type'] = null;
        }

        if ($request->boolean('is_international_address')) {
            $validated['address'] = trim((string) $validated['address_other']);
        } else {
            $location = app(PhilippineLocationService::class)->resolveAddressCodes(
                (string) $validated['region_code'],
                (string) $validated['province_code'],
                (string) $validated['city_code'],
                (string) $validated['barangay_code'],
            );
            if ($location === null) {
                throw ValidationException::withMessages([
                    'barangay_code' => 'Choose a valid region, province, city / municipality, and barangay.',
                ]);
            }

            $validated['address'] = implode(', ', array_filter([
                trim((string) ($validated['purok_street'] ?? '')),
                $location['barangay'],
                $location['city'],
                $location['province'],
                $location['region'],
            ], static fn (string $part): bool => $part !== ''));
        }

        $validated['address'] = validator(
            ['address' => $validated['address']],
            ['address' => ['required', 'string', 'max:255']],
        )->validate()['address'];
        $validated['full_name'] = implode(' ', array_filter([
            trim((string) $validated['first_name']),
            trim((string) ($validated['middle_name'] ?? '')),
            trim((string) $validated['last_name']),
            trim((string) ($validated['name_extension'] ?? '')),
        ], static fn (string $part): bool => $part !== ''));
        $validated['full_name'] = validator(
            ['full_name' => $validated['full_name']],
            ['full_name' => ['required', 'string', 'max:150']],
        )->validate()['full_name'];
        $validated['contact_number'] = $this->validatedContactNumber($request);
        $validated['time_in'] = now()->format('Y-m-d H:i:s');

        return $validated;
    }

    private function validatedContactNumber(Request $request): string
    {
        $countryCodes = array_keys($this->logbookTransfer->contactCountryCodes());
        $countryCode = (string) $request->input('country_code', '+63');
        $phoneRule = $countryCode === '+63'
            ? 'digits:11'
            : 'regex:/^[0-9]{4,15}$/';
        $validated = $request->validate([
            'country_code' => ['required', 'in:'.implode(',', $countryCodes)],
            'contact_number' => ['required', 'string', $phoneRule],
        ]);

        return $countryCode.' '.$validated['contact_number'];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function legacyRow(array $validated, ?User $user, bool $includeTemplate = true): array
    {
        $visitor = null;
        if (! empty($validated['visitor_account_id']) && Schema::hasTable('visitor_accounts')) {
            $visitor = DB::table('visitor_accounts')->where('id', $validated['visitor_account_id'])->first();
        }

        $fingerprint = $user?->employee_id ?: ($validated['fingerprint_id'] ?? null);
        $row = [
            'full_name' => $user?->name ?? $user?->full_name ?? ($visitor->full_name ?? null) ?? ($validated['full_name'] ?? 'Unknown'),
            'address' => $validated['address'],
            'contact_number' => $validated['contact_number'],
            'purpose_of_transaction' => $validated['purpose_of_transaction'],
            'fingerprint_id' => ($fingerprint === null || $fingerprint === '') ? 0 : (string) $fingerprint,
            'time_in' => $validated['time_in'],
        ];
        $personCategoryColumn = Schema::hasColumn('logbook', 'person_category')
            ? 'person_category'
            : (Schema::hasColumn('logbook', 'person_classification') ? 'person_classification' : null);
        if ($personCategoryColumn !== null && array_key_exists('person_category', $validated)) {
            $row[$personCategoryColumn] = $validated['person_category'];
        }

        if ($includeTemplate && Schema::hasColumn('logbook', 'fp_template')) {
            $row['fp_template'] = null;
        }

        if (Schema::hasColumn('logbook', 'transaction_type')
            && isset($validated['transaction_type'])
            && trim((string) $validated['transaction_type']) !== '') {
            $row['transaction_type'] = $validated['transaction_type'];
        }

        foreach ([
            'is_authorized_representative' => $validated['is_authorized_representative'] ?? false,
            'authorization_letter_verified' => $validated['authorization_letter_verified'] ?? false,
            'valid_id_verified' => $validated['valid_id_verified'] ?? false,
            'represented_person_name' => $validated['represented_person_name'] ?? null,
            'represented_person_classification' => $validated['represented_person_classification'] ?? null,
            'additional_notes' => $validated['additional_notes'] ?? null,
        ] as $column => $value) {
            if (Schema::hasColumn('logbook', $column) && $value !== null) {
                $row[$column] = is_bool($value) ? ($value ? 1 : 0) : $value;
            }
        }

        return $row;
    }
}
