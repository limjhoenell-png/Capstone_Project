<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'in:5,10,15,20,25,50,75,100'],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 10);
        $employees = $this->employeesPaginator($perPage);

        return view('admin.employees.index', [
            'employees' => $employees,
            'perPage' => $perPage,
            'liveSignature' => $this->employeesSignature($employees),
        ]);
    }

    public function liveSync(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'per_page' => ['nullable', 'integer', 'in:5,10,15,20,25,50,75,100'],
            ]);
            $perPage = (int) ($validated['per_page'] ?? 10);
            $employees = $this->employeesPaginator($perPage);
            $signature = $this->employeesSignature($employees);

            if ($request->query('signature') === $signature) {
                return response()->json([
                    'changed' => false,
                    'signature' => $signature,
                ]);
            }

            return response()->json([
                'changed' => true,
                'signature' => $signature,
                'html' => view('admin.employees.partials.live-region', [
                    'employees' => $employees,
                    'perPage' => $perPage,
                ])->render(),
                'perPage' => $perPage,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'changed' => false,
                'error' => 'Unable to sync employees data.',
            ], 500);
        }
    }

    public function attendances(User $employee): View
    {
        return view('admin.employees.attendances', [
            'employee' => $employee,
            'attendances' => Attendance::query()
                ->where('user_id', $employee->id)
                ->orderByDesc('attendance_date')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.employees.create');
    }

    public function show(User $employee): RedirectResponse
    {
        // Resource routes expose /admin/employees/{employee}; direct it to the existing edit flow.
        return redirect()->route('admin.employees.edit', $employee);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['full_name'] = $data['name'];
        User::query()->create($data);

        return redirect()->route('admin.employees.index')->with('status', 'Employee created.');
    }

    public function edit(User $employee): View
    {
        return view('admin.employees.edit', ['employee' => $employee]);
    }

    public function update(UpdateEmployeeRequest $request, User $employee): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $data['full_name'] = $data['name'];

        $employee->update($data);

        return redirect()->route('admin.employees.index')->with('status', 'Employee updated.');
    }

    public function destroy(Request $request, User $employee): RedirectResponse
    {
        if (Auth::id() === $employee->id) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $request->validate([
            'confirm_name' => ['required', 'string', 'in:'.$employee->name],
        ]);

        DB::transaction(function () use ($employee): void {
            $this->deleteEmployeeRecords($employee);
        });

        return redirect()->route('admin.employees.index')->with('status', 'Employee deleted.');
    }

    public function destroySelected(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);
        $ids = $validated['employee_ids'];
        $employees = User::query()
            ->whereIn('id', $ids)
            ->where('role', User::ROLE_EMPLOYEE)
            ->where('id', '!=', Auth::id())
            ->get();

        if ($employees->count() !== count($ids)) {
            return back()->withErrors(['employee_ids' => 'Only employee accounts can be deleted.']);
        }

        DB::transaction(function () use ($employees): void {
            foreach ($employees as $employee) {
                $this->deleteEmployeeRecords($employee);
            }
        });

        $count = $employees->count();

        return redirect()
            ->route('admin.employees.index')
            ->with('status', "Deleted {$count} employee ".($count === 1 ? 'account.' : 'accounts.'));
    }

    private function deleteEmployeeRecords(User $employee): void
    {
        if (Schema::hasTable('logbook') && $employee->employee_id !== null && $employee->employee_id !== '') {
            DB::table('logbook')
                ->whereRaw('CAST(fingerprint_id AS CHAR) = ?', [(string) $employee->employee_id])
                ->delete();
        }

        if (Schema::hasTable('logbook_entries')) {
            DB::table('logbook_entries')->where('user_id', $employee->id)->delete();
        }

        if (Schema::hasTable('attendances')) {
            DB::table('attendances')->where('user_id', $employee->id)->delete();
        }

        $employee->delete();
    }

    private function employeesPaginator(int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_EMPLOYEE])
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'employees_page')
            ->withQueryString();
    }

    private function employeesSignature(LengthAwarePaginator $employees): string
    {
        return sha1(json_encode([
            'total' => $employees->total(),
            'page' => $employees->currentPage(),
            'per_page' => $employees->perPage(),
            'items' => $employees->getCollection()->map(function (User $employee) {
                return [
                    'id' => $employee->id,
                    'name' => (string) $employee->name,
                    'email' => (string) $employee->email,
                    'role' => (string) $employee->role,
                    'position' => (string) $employee->position,
                    'contact_number' => (string) $employee->contact_number,
                    'updated_at' => (string) $employee->updated_at,
                ];
            })->all(),
        ], JSON_UNESCAPED_UNICODE));
    }
}
