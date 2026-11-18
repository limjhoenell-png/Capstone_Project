<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitorAccount;
use App\Services\LogbookAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BarangayLogbookPortalController extends Controller
{
    public function __construct(
        private LogbookAnalyticsService $analytics,
    ) {}

    public function index(): View
    {
        return view('admin.barangay_logbook.portal', [
            'title' => 'Logbook Reports',
        ]);
    }

    public function logbookLogs(Request $request): View
    {
        return app(LogbookController::class)->renderIndexHtml($request, [
            'logbooksListUrl' => route('admin.barangay-logbook.logs'),
            'pageTitle' => 'Logbook Records',
            'backToLogbookHubUrl' => route('admin.barangay-logbook.index'),
        ]);
    }

    public function analytics(): JsonResponse
    {
        return response()->json($this->analytics->summary());
    }

    public function report(Request $request): Response
    {
        $period = $this->period($request);
        $summary = $this->analytics->summary();

        $filename = sprintf(
            'logbook-report-%s-%s.pdf',
            $period,
            now()->format('Y-m-d')
        );

        return Pdf::loadView('admin.barangay_logbook.report', [
            'summary' => $summary,
            'period' => $period,
            'periodLabel' => match ($period) {
                'week' => 'Week',
                'month' => 'Month',
                'year' => 'Year',
                default => 'Day',
            },
            'seriesKey' => match ($period) {
                'week' => 'by_week',
                'month' => 'by_month',
                'year' => 'by_year',
                default => 'by_day',
            },
        ])->setPaper('a4')->download($filename);
    }

    private function period(Request $request): string
    {
        $period = $request->string('period', 'day')->toString();

        return in_array($period, ['day', 'week', 'month', 'year'], true) ? $period : 'day';
    }

    public function storeVisitor(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
        ]);

        VisitorAccount::query()->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Logbook account saved successfully.',
            ]);
        }

        return redirect()
            ->route('admin.barangay-logbook.index')
            ->with('status', 'Logbook account registered.');
    }
}
