<?php

namespace App\Support;

class TopbarBreadcrumbs
{
    /**
     * @return list<array{label: string, url: ?string}>
     */
    public static function resolve(?string $title = null): array
    {
        $route = request()->route()?->getName();
        $crumbs = self::trail(is_string($route) ? $route : null);

        if ($crumbs === []) {
            return [[
                'label' => $title !== null && $title !== '' ? $title : 'ReCORD',
                'url' => null,
            ]];
        }

        $last = count($crumbs) - 1;
        if ($title !== null && $title !== '') {
            $crumbs[$last]['label'] = $title;
        }
        $crumbs[$last]['url'] = null;

        return $crumbs;
    }

    /**
     * @return list<array{label: string, url: ?string}>
     */
    private static function trail(?string $route): array
    {
        if ($route === null) {
            return [];
        }

        $dashboard = self::link('Dashboard', 'admin.dashboard');
        $myDashboard = self::link('Dashboard', 'employee.dashboard');

        return match (true) {
            $route === 'admin.dashboard' => [self::current('Dashboard')],
            $route === 'admin.employees.index' => [$dashboard, self::current('Employees')],
            $route === 'admin.employees.attendances' => [$dashboard, self::link('Employees', 'admin.employees.index'), self::current('Attendance')],
            str_starts_with($route, 'admin.employees.') => [$dashboard, self::link('Employees', 'admin.employees.index'), self::current('Employee')],
            $route === 'admin.attendances.index' => [$dashboard, self::current('Attendance')],
            str_starts_with($route, 'admin.attendances.') => [$dashboard, self::link('Attendance', 'admin.attendances.index'), self::current('Attendance')],
            $route === 'admin.payroll.index' => [$dashboard, self::current('Payroll')],
            str_starts_with($route, 'admin.payroll.') => [$dashboard, self::link('Payroll', 'admin.payroll.index'), self::current('Payroll')],
            $route === 'admin.payslips.index' => [$dashboard, self::current('Payslips')],
            str_starts_with($route, 'admin.payslips.') => [$dashboard, self::link('Payslips', 'admin.payslips.index'), self::current('Payslip')],
            $route === 'admin.holidays.index' => [$dashboard, self::current('Holidays')],
            $route === 'admin.visitors.index' => [$dashboard, self::link('Logbook Reports', 'admin.barangay-logbook.index'), self::current('Logbook Accounts')],
            $route === 'admin.barangay-logbook.index' => [$dashboard, self::current('Logbook Reports')],
            $route === 'admin.barangay-logbook.logs', $route === 'admin.logbooks.index' => [$dashboard, self::link('Logbook Reports', 'admin.barangay-logbook.index'), self::current('Logbook Records')],
            str_starts_with($route, 'admin.logbooks.') => [$dashboard, self::link('Logbook Reports', 'admin.barangay-logbook.index'), self::link('Logbook Records', 'admin.barangay-logbook.logs'), self::current('Logbook')],
            $route === 'employee.dashboard' => [self::current('Dashboard')],
            $route === 'employee.attendances.index' => [$myDashboard, self::current('My Attendance')],
            $route === 'employee.payslips.index' => [$myDashboard, self::current('Payslips')],
            str_starts_with($route, 'employee.payslips.') => [$myDashboard, self::link('Payslips', 'employee.payslips.index'), self::current('Payslip')],
            $route === 'employee.logbooks.index' => [$myDashboard, self::current('Logbook')],
            $route === 'employee.profile.edit' => [$myDashboard, self::current('Profile')],
            default => [],
        };
    }

    /**
     * @return array{label: string, url: string}
     */
    private static function link(string $label, string $route): array
    {
        return ['label' => $label, 'url' => route($route)];
    }

    /**
     * @return array{label: string, url: null}
     */
    private static function current(string $label): array
    {
        return ['label' => $label, 'url' => null];
    }
}
