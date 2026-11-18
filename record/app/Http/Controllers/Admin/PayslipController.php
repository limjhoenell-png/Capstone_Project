<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProcessPayslipsRequest;
use App\Mail\PayslipPublished;
use App\Models\PayrollSetting;
use App\Models\Payslip;
use App\Models\PayslipRequest;
use App\Models\User;
use App\Models\UserNotice;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {}

    public function index(Request $request): View
    {
        $periodStart = $request->query('period_start');
        $periodEnd = $request->query('period_end');
        $employeeId = $request->query('employee_id');
        $filters = $request->validate([
            'per_page' => ['nullable', 'integer', 'in:5,10,15,20,25,50,75,100'],
        ]);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $payslips = $this->payslipPaginator($perPage, $periodStart, $periodEnd, $employeeId);

        $employees = User::query()
            ->personnel()
            ->orderBy('name')
            ->get();

        return view('admin.payslips.index', [
            'title' => 'Payslips',
            'payslips' => $payslips,
            'payslipRequests' => PayslipRequest::query()
                ->with('user')
                ->whereIn('status', ['pending', 'reviewing'])
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderBy('created_at')
                ->get(),
            'employees' => $employees,
            'filter_period_start' => $periodStart,
            'filter_period_end' => $periodEnd,
            'filter_employee_id' => $employeeId,
            'perPage' => $perPage,
        ]);
    }

    public function updateRequestStatus(Request $request, PayslipRequest $payslipRequest): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:reviewing,fulfilled,dismissed'],
        ]);

        $payslipRequest->update([
            'status' => $validated['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $payslipRequest->status,
                'pending_count' => PayslipRequest::query()->where('status', 'pending')->count(),
            ]);
        }

        return back()->with('status', 'Past payslip request status updated.');
    }

    public function pendingRequests(): JsonResponse
    {
        try {
            $requests = PayslipRequest::query()
                ->with('user:id,name')
                ->whereIn('status', ['pending', 'reviewing'])
                ->orderBy('created_at')
                ->get()
                ->map(function (PayslipRequest $payslipRequest): array {
                    $payslip = Payslip::query()
                        ->where('user_id', $payslipRequest->user_id)
                        ->whereDate('period_start', $payslipRequest->period_start)
                        ->whereDate('period_end', $payslipRequest->period_end)
                        ->where('kind', 'regular')
                        ->first();

                    return [
                        'id' => $payslipRequest->id,
                        'employee' => $payslipRequest->user?->name ?? 'Employee account removed',
                        'period_start' => $payslipRequest->period_start->toDateString(),
                        'period_end' => $payslipRequest->period_end->toDateString(),
                        'period_label' => $payslipRequest->period_start->format('F Y'),
                        'submitted_at' => $payslipRequest->created_at?->format('M j, Y g:i A'),
                        'status' => $payslipRequest->status,
                        'employee_id' => $payslipRequest->user_id,
                        'review_url' => route('admin.payroll.index', [
                            'period_start' => $payslipRequest->period_start->toDateString(),
                            'period_end' => $payslipRequest->period_end->toDateString(),
                            'employee_id' => $payslipRequest->user_id,
                        ]),
                        'generate_url' => route('admin.payslip-requests.generate', $payslipRequest),
                        'request_status_url' => route('admin.payslip-requests.status', $payslipRequest),
                        'approve_url' => $payslip?->status === 'draft'
                            ? route('admin.payslips.approve', $payslip)
                            : null,
                        'view_url' => $payslip !== null
                            ? route('admin.payslips.show', $payslip)
                            : null,
                        'payslip_status' => $payslip?->status,
                    ];
                });

            return response()->json([
                'requests' => $requests,
                'pending_count' => PayslipRequest::query()->where('status', 'pending')->count(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => 'Unable to load pending payslip requests.',
            ], 500);
        }
    }

    public function liveSync(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'per_page' => ['nullable', 'integer', 'in:5,10,15,20,25,50,75,100'],
            ]);
            $perPage = (int) ($validated['per_page'] ?? 10);
            $periodStart = $request->query('period_start');
            $periodEnd = $request->query('period_end');
            $employeeId = $request->query('employee_id');
            $payslips = $this->payslipPaginator($perPage, $periodStart, $periodEnd, $employeeId);

            return response()->json([
                'changed' => true,
                'html' => view('admin.payslips.partials.table-region', [
                    'payslips' => $payslips,
                    'perPage' => $perPage,
                    'filter_period_start' => $periodStart,
                    'filter_period_end' => $periodEnd,
                    'filter_employee_id' => $employeeId,
                ])->render(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'changed' => false,
                'error' => 'Unable to sync payslips data.',
            ], 500);
        }
    }

    public function show(Payslip $payslip): View
    {
        $payslip->load('user');

        return view('admin.payslips.show', [
            'title' => 'Payslip — '.$payslip->employee_display_name,
            'payslip' => $payslip,
        ]);
    }

    public function pdf(Payslip $payslip): Response
    {
        $payslip->load('user');

        $filename = sprintf(
            'payslip-%s-%s-%s.pdf',
            preg_replace('/[^a-zA-Z0-9_-]+/', '_', $payslip->employee_display_name),
            $payslip->period_start->format('Y-m-d'),
            $payslip->period_end->format('Y-m-d')
        );

        $pdf = Pdf::loadView('admin.payslips.pdf', ['payslip' => $payslip])
            ->setPaper('a4');

        return $pdf->download($filename);
    }

    private function payslipPaginator(
        int $perPage,
        ?string $periodStart,
        ?string $periodEnd,
        ?string $employeeId
    ): LengthAwarePaginator {
        $query = Payslip::query()->with('user')->orderByDesc('period_end')->orderBy('employee_display_name');

        if (($periodStart ?? '') !== '' && ($periodEnd ?? '') !== '') {
            $query->whereDate('period_start', Carbon::parse($periodStart)->toDateString())
                ->whereDate('period_end', Carbon::parse($periodEnd)->toDateString());
        }

        if (($employeeId ?? '') !== '') {
            $query->where('user_id', (int) $employeeId);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function process(ProcessPayslipsRequest $request): RedirectResponse
    {
        $settings = PayrollSetting::current();

        $from = Carbon::parse($request->validated('period_start'))->startOfDay();
        $to = Carbon::parse($request->validated('period_end'))->endOfDay();

        $employees = User::query()
            ->personnel()
            ->when(
                $request->validated('employee_id'),
                fn ($query, $employeeId) => $query->whereKey((int) $employeeId)
            )
            ->with('payrollDeductions')
            ->orderBy('name')
            ->get();

        foreach ($employees as $employee) {
            $this->generatePayslip($employee, $from, $to, $settings, 'draft');
        }

        if ($employees->isNotEmpty()) {
            PayslipRequest::query()
                ->whereIn('user_id', $employees->modelKeys())
                ->whereDate('period_start', $from->toDateString())
                ->whereDate('period_end', $to->toDateString())
                ->whereIn('status', ['pending', 'reviewing'])
                ->update([
                    'status' => 'reviewing',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);
        }

        return redirect()
            ->route('admin.payslips.index', [
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
                'employee_id' => $request->validated('employee_id'),
            ])
            ->with('status', 'Payslips generated for '.$employees->count().' employee(s) for the selected period.');
    }

    public function generateAndPublishRequest(Request $request, PayslipRequest $payslipRequest): RedirectResponse
    {
        [$employee, $from, $to, $payslip, $alreadyPublished] = DB::transaction(function () use ($request, $payslipRequest): array {
            $lockedRequest = PayslipRequest::query()
                ->lockForUpdate()
                ->findOrFail($payslipRequest->id);
            abort_unless(in_array($lockedRequest->status, ['pending', 'reviewing'], true), 409);

            $employee = $lockedRequest->user;
            abort_unless($employee !== null && $employee->role === User::ROLE_EMPLOYEE, 404);

            $from = $lockedRequest->period_start->copy()->startOfDay();
            $to = $lockedRequest->period_end->copy()->endOfDay();
            $existingPayslip = Payslip::query()
                ->where('user_id', $employee->id)
                ->whereDate('period_start', $from->toDateString())
                ->whereDate('period_end', $to->toDateString())
                ->where('kind', 'regular')
                ->first();
            $alreadyPublished = $existingPayslip?->isVisibleToEmployee() ?? false;

            if ($alreadyPublished) {
                $payslip = $existingPayslip;
            } else {
                $payslip = $this->generatePayslip($employee, $from, $to, PayrollSetting::current(), 'approved');
                $payslip->update([
                    'approved_at' => $payslip->approved_at ?? now(),
                    'published_at' => $payslip->published_at ?? now(),
                ]);
            }
            $lockedRequest->update([
                'status' => 'fulfilled',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            return [$employee, $from, $to, $payslip, $alreadyPublished];
        });

        if (! $alreadyPublished) {
            $this->notifyEmployee($payslip->fresh() ?? $payslip);
        }

        return redirect()
            ->route('admin.payroll.index', [
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
                'employee_id' => $employee->id,
            ])
            ->with('status', 'Payslip generated and published to '.$employee->name.'.');
    }

    private function generatePayslip(User $employee, Carbon $from, Carbon $to, PayrollSetting $settings, string $status): Payslip
    {
        $quote = $this->payrollService->quote($employee, $from, $to, $settings);
        $deductionItems = $quote['deduction_lines'] === []
            ? null
            : $this->payrollService->deductionItemsForPayslip($quote['deduction_lines']);

        return Payslip::query()->updateOrCreate(
            [
                'user_id' => $employee->id,
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
                'kind' => 'regular',
            ],
            [
                'days_worked' => $quote['split']['days_worked'],
                'total_hours' => $quote['split']['total_hours'],
                'regular_hours' => $quote['split']['regular_hours'],
                'overtime_hours' => $quote['split']['overtime_hours'],
                'equivalent_days' => $quote['equivalent_days'],
                'pay_rate_type' => $employee->pay_rate_type ?? User::PAY_RATE_DAILY,
                'pay_rate_amount' => $employee->pay_rate,
                'gross_pay' => $quote['gross_pay'],
                'earning_items' => $quote['earning_lines'] === [] ? null : $quote['earning_lines'],
                'regular_pay' => $quote['regular_pay'],
                'overtime_pay' => $quote['overtime_pay'],
                'night_diff_hours' => $quote['night_diff_hours'],
                'night_diff_pay' => $quote['night_diff_pay'],
                'holiday_pay' => $quote['holiday_pay'],
                'rest_day_pay' => $quote['rest_day_pay'],
                'undertime_hours' => $quote['undertime_hours'],
                'deductions_total' => $quote['deductions_total'],
                'deduction_items' => $deductionItems,
                'net_pay' => $quote['net_pay'],
                'status' => $status,
                'employee_display_name' => $this->payrollService->employeeDisplayName($employee),
                'employee_identifier' => $this->payrollService->employeeIdentifier($employee),
                'processed_at' => now(),
                'approved_at' => $status === 'approved' ? now() : null,
                'published_at' => $status === 'approved' ? now() : null,
            ]
        );
    }

    public function approve(Payslip $payslip): RedirectResponse
    {
        $payslip->update([
            'status' => 'approved',
            'approved_at' => now(),
            'published_at' => now(),
        ]);

        PayslipRequest::query()
            ->where('user_id', $payslip->user_id)
            ->whereDate('period_start', $payslip->period_start)
            ->whereDate('period_end', $payslip->period_end)
            ->whereIn('status', ['pending', 'reviewing'])
            ->update([
                'status' => 'fulfilled',
                'reviewed_by' => request()->user()->id,
                'reviewed_at' => now(),
            ]);

        $this->notifyEmployee($payslip);

        return back()->with('status', 'Payslip published to '.$payslip->employee_display_name.'.');
    }

    public function markPaid(Payslip $payslip): RedirectResponse
    {
        $payslip->update([
            'status' => 'paid',
            'approved_at' => $payslip->approved_at ?? now(),
            'published_at' => $payslip->published_at ?? now(),
        ]);

        if ($payslip->published_at === null) {
            $this->notifyEmployee($payslip->fresh() ?? $payslip);
        }

        return back()->with('status', 'Payslip marked paid.');
    }

    private function notifyEmployee(Payslip $payslip): void
    {
        $payslip->loadMissing('user');
        $user = $payslip->user;
        if ($user === null) {
            return;
        }

        UserNotice::query()->create([
            'user_id' => $user->id,
            'payslip_id' => $payslip->id,
            'title' => 'Payslip published',
            'body' => sprintf(
                'Your %s payslip for %s to %s is ready. Net pay: %s.',
                str_replace('_', ' ', (string) $payslip->kind),
                $payslip->period_start->format('M j, Y'),
                $payslip->period_end->format('M j, Y'),
                number_format((float) $payslip->net_pay, 2)
            ),
        ]);

        try {
            Mail::to($user->email)->send(new PayslipPublished($payslip));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
