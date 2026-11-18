<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmployeePortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LogbookController extends Controller
{
    public function __construct(
        private EmployeePortalService $portal,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        abort_unless($user->fresh()?->canAccessLogbook(), 403, 'Access Denied: Employee is not authorized to access Logbook module.');

        $params = $request->query();
        if (! $request->has('per_page')
            || $request->has('legacy_per_page')
            || $request->has('accounts_per_page')) {
            $perPage = $request->integer('per_page', 10);
            $allowed = [5, 10, 15, 20, 25, 50, 75, 100];
            if (! in_array($perPage, $allowed, true)) {
                $perPage = 10;
            }
            unset($params['legacy_per_page'], $params['accounts_per_page']);
            $params['per_page'] = $perPage;

            return redirect()->route('employee.logbooks.index', $params);
        }

        $data = $this->portal->logbooksIndex($user->fresh() ?? $user, $request);

        return view('employee.logbooks.index', $data);
    }
}
