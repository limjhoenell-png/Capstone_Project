<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotice;
use App\Services\EmployeeNotificationService;
use App\Services\EmployeePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LiveSyncController extends Controller
{
    public function __construct(
        private EmployeePortalService $portal,
        private EmployeeNotificationService $notifications,
    ) {}

    private function portalUser(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user->fresh() ?? $user;
    }

    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $this->portalUser();
            $data = $this->portal->dashboard($user);
            $notifications = $this->notifications->payload($user);

            if ($request->query('signature') === $data['liveSignature']) {
                return response()->json([
                    'changed' => false,
                    'signature' => $data['liveSignature'],
                    ...$notifications,
                ]);
            }

            $attendancePage = $this->portal->dashboardAttendancePage($data['latestAttendance'], $request);

            return response()->json([
                'changed' => true,
                'signature' => $data['liveSignature'],
                'html' => view('employee.dashboard.partials.live-region', [
                    'myAttendanceCount' => $data['myAttendanceCount'],
                    'logbookCount' => $data['logbookCount'] ?? 0,
                    'monthlySummary' => $data['monthlySummary'],
                    'latestAttendance' => $data['latestAttendance'],
                    'recentAttendance' => $attendancePage['rows'],
                    'weekPage' => $attendancePage['page'],
                    'weekPages' => $attendancePage['pages'],
                    'weekMonth' => $attendancePage['month'],
                    'notices' => $user->notices()->with('payslip')->latest()->limit(EmployeeNotificationService::PER_PAGE)->get(),
                    'unreadNoticeCount' => $notifications['unread_count'],
                    'notificationPayload' => $notifications,
                ])->render(),
                ...$notifications,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'changed' => false,
                'error' => 'Unable to sync dashboard.',
            ], 500);
        }
    }

    public function markNotificationsRead(): JsonResponse
    {
        try {
            $user = $this->portalUser();
            $user->notices()->whereNull('read_at')->update(['read_at' => now()]);

            return response()->json($this->notifications->payload($user));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => 'Unable to update notification status.',
            ], 500);
        }
    }

    public function notifications(Request $request): JsonResponse
    {
        $validated = $request->validate(['page' => ['nullable', 'integer', 'min:1']]);

        return response()->json($this->notifications->payload(
            $this->portalUser(),
            (int) ($validated['page'] ?? 1),
        ));
    }

    public function deleteNotification(Request $request, UserNotice $notice): JsonResponse
    {
        $user = $this->portalUser();
        $validated = $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $page = (int) ($validated['page'] ?? 1);
        $user->notices()->whereKey($notice->id)->firstOrFail()->delete();

        return response()->json([
            'deleted' => true,
            ...$this->notifications->payload($user, $page),
        ]);
    }

    public function deleteNotifications(Request $request): JsonResponse
    {
        $user = $this->portalUser();
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'ids' => ['required', 'array', 'min:1', 'max:'.EmployeeNotificationService::PER_PAGE],
            'ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('user_notices', 'id')->where('user_id', $user->id),
            ],
        ]);
        $user->notices()->whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'deleted' => true,
            ...$this->notifications->payload($user, (int) ($validated['page'] ?? 1)),
        ]);
    }

    public function attendances(Request $request): JsonResponse
    {
        try {
            $user = $this->portalUser();
            $data = $this->portal->attendancesIndex($user);

            if ($request->query('signature') === $data['liveSignature']) {
                return response()->json([
                    'changed' => false,
                    'signature' => $data['liveSignature'],
                ]);
            }

            $perPage = $request->integer('per_page', 10);
            if (! in_array($perPage, [10, 15, 20, 30], true)) {
                $perPage = 10;
            }
            $attendancePage = $this->portal->paginateAttendanceSummaries(
                $data['logDailySummaries'],
                $request,
                $perPage,
            );

            return response()->json([
                'changed' => true,
                'signature' => $data['liveSignature'],
                'html' => view('employee.attendances.partials.live-region', [
                    'logDailySummaries' => $data['logDailySummaries'],
                    'attendancePage' => $attendancePage,
                    'perPage' => $perPage,
                ])->render(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'changed' => false,
                'error' => 'Unable to sync attendance.',
            ], 500);
        }
    }

    public function logbooks(Request $request): JsonResponse
    {
        try {
            $data = $this->portal->logbooksIndex($this->portalUser(), $request);

            if ($request->query('signature') === $data['liveSignature']) {
                return response()->json([
                    'changed' => false,
                    'signature' => $data['liveSignature'],
                ]);
            }

            return response()->json([
                'changed' => true,
                'signature' => $data['liveSignature'],
                'html' => view('employee.logbooks.partials.live-region', [
                    'legacyEntries' => $data['legacyEntries'],
                    'entries' => $data['entries'],
                    'accounts' => $data['accounts'],
                    'legacyPerPage' => $data['legacyPerPage'],
                    'accountsPerPage' => $data['accountsPerPage'],
                    'pageSizes' => $data['pageSizes'],
                    'globalPerPage' => $data['globalPerPage'],
                    'search' => $data['search'],
                ])->render(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'changed' => false,
                'error' => 'Unable to sync logbook.',
            ], 500);
        }
    }
}
