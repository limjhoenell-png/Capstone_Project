<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmployeePortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        private EmployeePortalService $portal,
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'in:10,15,20,30'],
            'attendance_page' => ['nullable', 'integer', 'min:1'],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 10);
        $user = $user->fresh() ?? $user;

        $data = $this->portal->attendancesIndex($user);
        $data['attendancePage'] = $this->portal->paginateAttendanceSummaries(
            $data['logDailySummaries'],
            $request,
            $perPage,
        );

        return view('employee.attendances.index', $data + ['perPage' => $perPage]);
    }
}
