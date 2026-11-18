<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendancePunchRequest;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceRecordingService;
use App\Support\AttendanceTimeWindow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceRecordingService $recording,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Attendance::class);
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'in:5,10,15,20,25,50,75,100'],
        ]);

        return view('admin.attendances.index', $this->attendancePayload(
            $validated['date'] ?? null,
            (int) ($validated['per_page'] ?? 10),
        ));
    }

    public function liveSync(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        try {
            $validated = $request->validate([
                'date' => ['nullable', 'date_format:Y-m-d'],
                'per_page' => ['nullable', 'integer', 'in:5,10,15,20,25,50,75,100'],
            ]);
            $payload = $this->attendancePayload(
                $validated['date'] ?? null,
                (int) ($validated['per_page'] ?? 10),
            );

            if ($request->query('signature') === $payload['liveSignature']) {
                return response()->json([
                    'changed' => false,
                    'signature' => $payload['liveSignature'],
                ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
            }

            return response()->json([
                'changed' => true,
                'signature' => $payload['liveSignature'],
                'html' => view('admin.attendances.partials.live-region', $payload)->render(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'changed' => false,
                'error' => 'Unable to sync attendance data.',
            ], 500)->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }
    }

    public function create(): View
    {
        $this->authorize('create', Attendance::class);

        return view('admin.attendances.create', [
            'employees' => User::query()->where('role', User::ROLE_EMPLOYEE)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);
        $this->recording->saveDaily($request->validated());

        return redirect()->route('admin.attendances.index')->with('status', 'Attendance created.');
    }

    public function punch(StoreAttendancePunchRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $employee = User::query()
            ->whereKey($validated['user_id'])
            ->where('role', User::ROLE_EMPLOYEE)
            ->firstOrFail();

        $this->recording->recordPunch(
            $employee,
            Carbon::parse($validated['punched_at'] ?? now())
        );

        return redirect()->route('admin.attendances.index')->with('status', 'Attendance punch recorded.');
    }

    public function edit(Attendance $attendance): View
    {
        $this->authorize('update', $attendance);

        return view('admin.attendances.edit', [
            'attendance' => $attendance,
            'employees' => User::query()->where('role', User::ROLE_EMPLOYEE)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('update', $attendance);
        $attendance->update($request->validated());
        $attendance->recalculateTotalHours();
        $attendance->save();

        return redirect()->route('admin.attendances.index')->with('status', 'Attendance updated.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $this->authorize('delete', $attendance);
        $attendance->delete();

        return redirect()->route('admin.attendances.index')->with('status', 'Attendance deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attendancePayload(?string $filterDate = null, int $perPage = 10): array
    {
        $attendances = Attendance::query()
            ->with('user')
            ->when($filterDate !== null, fn ($query) => $query->whereDate('attendance_date', $filterDate))
            ->orderByDesc('attendance_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'merged_page')
            ->withQueryString();

        $attendances->setCollection($attendances->getCollection()->map(function (Attendance $attendance): array {
            $validTimeIn = $attendance->time_in !== null && AttendanceTimeWindow::allows($attendance->time_in);

            return [
                'user_id' => $attendance->user_id,
                'name' => $attendance->user?->name ?: 'Employee',
                'date' => $attendance->attendance_date?->format('Y-m-d'),
                'time_in' => $attendance->time_in,
                'time_out' => $attendance->time_out,
                'hours' => $validTimeIn
                    ? min((float) $attendance->total_hours, AttendanceTimeWindow::MAX_PAYABLE_HOURS)
                    : 0.0,
                'status' => $validTimeIn ? 'Present' : ($attendance->time_in === null ? 'Open' : 'Absent'),
                'source' => 'attendance',
                'remarks' => (string) $attendance->remarks,
                'attendance_id' => $attendance->id,
            ];
        }));

        return [
            'mergedDays' => $attendances,
            'filterDate' => $filterDate,
            'perPage' => $perPage,
            'liveSignature' => sha1(json_encode([
                'filter_date' => $filterDate,
                'per_page' => $perPage,
                'total' => $attendances->total(),
                'page' => $attendances->currentPage(),
                'rows' => $attendances->getCollection()->map(fn (array $row) => [
                    $row['attendance_id'],
                    $row['user_id'],
                    $row['date'],
                    $row['time_in']?->toDateTimeString(),
                    $row['time_out']?->toDateTimeString(),
                    $row['hours'],
                    $row['status'],
                    $row['remarks'],
                ])->all(),
            ], JSON_UNESCAPED_UNICODE)),
        ];
    }
}
