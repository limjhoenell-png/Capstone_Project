<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Models\PayslipRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->employee();
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'in:10,15,20,30'],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 10);

        return view('employee.payslips.index', [
            'payslips' => Payslip::query()
                ->where('user_id', $user->id)
                ->where(function ($query): void {
                    $query->whereIn('status', ['approved', 'paid'])
                        ->orWhereNull('status');
                })
                ->orderByDesc('period_end')
                ->paginate($perPage)
                ->withQueryString(),
            'perPage' => $perPage,
            'earliestRequestMonth' => ($user->employment_start_date ?? $user->created_at)?->copy()->startOfMonth()->format('Y-m') ?? now()->format('Y-m'),
            'latestRequestMonth' => now()->subMonth()->format('Y-m'),
            'payslipRequests' => PayslipRequest::query()
                ->where('user_id', $user->id)
                ->orderByDesc('period_start')
                ->get(),
        ]);
    }

    public function requestPast(Request $request): RedirectResponse
    {
        $user = $this->employee();
        $validated = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
        ]);
        $periodStart = Carbon::createFromFormat('!Y-m', $validated['period'])->startOfMonth();
        $employmentStart = $user->employment_start_date ?? $user->created_at;
        $earliestMonth = $employmentStart?->copy()->startOfMonth() ?? now()->startOfMonth();
        $latestMonth = now()->subMonth()->startOfMonth();

        if ($periodStart->lt($earliestMonth) || $periodStart->gt($latestMonth)) {
            return back()
                ->withErrors(['period' => 'Choose a past month from your employment start month through last month.'])
                ->withInput();
        }

        $exists = PayslipRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('period_start', $periodStart->toDateString())
            ->exists();
        if ($exists) {
            return back()
                ->withErrors(['period' => 'A request for this month has already been submitted.'])
                ->withInput();
        }

        PayslipRequest::query()->create([
            'user_id' => $user->id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodStart->copy()->endOfMonth()->toDateString(),
            'status' => 'pending',
        ]);

        return redirect()
            ->route('employee.payslips.index')
            ->with('status', 'Your past payslip request was sent to the admin for review.');
    }

    public function show(Payslip $payslip): View
    {
        $this->authorizePayslip($payslip);

        return view('admin.payslips.show', [
            'title' => 'My payslip',
            'payslip' => $payslip,
        ]);
    }

    public function pdf(Payslip $payslip): Response
    {
        $this->authorizePayslip($payslip);

        return Pdf::loadView('admin.payslips.pdf', ['payslip' => $payslip])->setPaper('a4')->download(
            'payslip-'.$payslip->period_start->format('Y-m-d').'.pdf'
        );
    }

    private function authorizePayslip(Payslip $payslip): void
    {
        $user = $this->employee();
        abort_unless((int) $payslip->user_id === (int) $user->id && $payslip->isVisibleToEmployee(), 403);
    }

    private function employee(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
