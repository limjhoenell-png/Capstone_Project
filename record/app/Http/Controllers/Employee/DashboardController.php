<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmployeeNotificationService;
use App\Services\EmployeePortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private EmployeePortalService $portal,
        private EmployeeNotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $user = $user->fresh() ?? $user;

        $data = $this->portal->dashboard($user);
        $attendancePage = $this->portal->dashboardAttendancePage($data['latestAttendance'], $request);
        $data['recentAttendance'] = $attendancePage['rows'];
        $data['weekPage'] = $attendancePage['page'];
        $data['weekPages'] = $attendancePage['pages'];
        $data['weekMonth'] = $attendancePage['month'];
        $notificationPayload = $this->notifications->payload($user);
        $data['notices'] = $user->notices()->with('payslip')->latest()->limit(EmployeeNotificationService::PER_PAGE)->get();
        $data['unreadNoticeCount'] = $notificationPayload['unread_count'];
        $data['notificationPayload'] = $notificationPayload;

        return view('employee.dashboard', $data);
    }
}
