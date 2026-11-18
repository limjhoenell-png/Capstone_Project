@php
    $crumbs = $breadcrumbs ?? \App\Support\TopbarBreadcrumbs::resolve($pageTitle ?? null);
    $currentCrumb = $crumbs[array_key_last($crumbs)] ?? [
        'label' => $pageTitle ?? 'ReCORD',
        'url' => null,
    ];
    $backCrumb = null;
    foreach (array_slice($crumbs, 0, -1) as $crumb) {
        if (!empty($crumb['url'])) {
            $backCrumb = $crumb;
        }
    }
    if (request()->routeIs('admin.employees.index', 'admin.attendances.index', 'admin.payroll.index', 'admin.payslips.index', 'admin.barangay-logbook.index', 'admin.barangay-logbook.logs', 'admin.logbooks.index', 'admin.visitors.index')) {
        $backCrumb = null;
    }
@endphp
<nav class="topbar-crumbs" aria-label="Page navigation">
    @if ($backCrumb)
        <a class="topbar-back-link" href="{{ $backCrumb['url'] }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Back to {{ $backCrumb['label'] }}
        </a>
    @endif
    <h1 class="topbar-page-context" @if(!$backCrumb) aria-current="page" @endif>{{ $currentCrumb['label'] }}</h1>
</nav>
