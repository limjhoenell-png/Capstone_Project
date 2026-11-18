<?php

use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\BarangayLogbookPortalController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmployeeController as AdminEmployeeController;
use App\Http\Controllers\Admin\EmployeePayrollDeductionController as AdminEmployeePayrollDeductionController;
use App\Http\Controllers\Admin\HolidayController as AdminHolidayController;
use App\Http\Controllers\Admin\LogbookController as AdminLogbookController;
use App\Http\Controllers\Admin\PayrollController as AdminPayrollController;
use App\Http\Controllers\Admin\PasswordResetRequestController as AdminPasswordResetRequestController;
use App\Http\Controllers\Admin\PayslipController as AdminPayslipController;
use App\Http\Controllers\Admin\VisitorAccountController as AdminVisitorAccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\LiveSyncController as EmployeeLiveSyncController;
use App\Http\Controllers\Employee\LogbookController as EmployeeLogbookController;
use App\Http\Controllers\Employee\PayslipController as EmployeePayslipController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('password.change');

    Route::get('/', function () {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('employee.dashboard');
    })->name('home');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
        Route::get('/password-reset-requests', [AdminPasswordResetRequestController::class, 'index'])->name('password-reset-requests.index');
        Route::put('/password-reset-requests/{passwordResetRequest}/resolve', [AdminPasswordResetRequestController::class, 'resolve'])->name('password-reset-requests.resolve');
        Route::post('/password-reset-requests/{passwordResetRequest}/dismiss', [AdminPasswordResetRequestController::class, 'dismiss'])->name('password-reset-requests.dismiss');
        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::get('barangay-logbook', [BarangayLogbookPortalController::class, 'index'])->name('barangay-logbook.index');
        Route::get('barangay-logbook/logbook-logs', [BarangayLogbookPortalController::class, 'logbookLogs'])->name('barangay-logbook.logs');
        Route::get('barangay-logbook/print-records', [AdminLogbookController::class, 'printRecords'])->name('barangay-logbook.print-records');
        Route::get('barangay-logbook/analytics', [BarangayLogbookPortalController::class, 'analytics'])->name('barangay-logbook.analytics');
        Route::get('barangay-logbook/report', [BarangayLogbookPortalController::class, 'report'])->name('barangay-logbook.report');
        Route::post('barangay-logbook/visitors', [BarangayLogbookPortalController::class, 'storeVisitor'])->name('barangay-logbook.visitors.store');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics/{path?}', fn () => redirect()->route('admin.dashboard'))
            ->where('path', '.*')
            ->name('legacy-analytics');
        Route::get('/hris/{path?}', fn () => redirect()->route('admin.dashboard'))
            ->where('path', '.*')
            ->name('legacy-hris');
        Route::get('/payroll', [AdminPayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/createannualbenefits', [AdminPayrollController::class, 'createAnnualBenefit'])->name('payroll.annual-benefits.create');
        Route::get('/payroll/print', [AdminPayrollController::class, 'printReport'])->name('payroll.print');
        Route::post('/payroll/print-layout', [AdminPayrollController::class, 'updatePrintLayout'])->name('payroll.print-layout.update');
        Route::post('/payroll/annual-benefits', [AdminPayrollController::class, 'storeAnnualBenefit'])->name('payroll.annual-benefits.store');
        Route::get('/payroll/annual-benefits/preview', [AdminPayrollController::class, 'previewAnnualBenefit'])->name('payroll.annual-benefits.preview');
        Route::get('/payroll/employees/{employee}/breakdown', [AdminPayrollController::class, 'breakdown'])->name('payroll.breakdown');
        Route::get('/payroll/settings', [AdminPayrollController::class, 'settings'])->name('payroll.settings');
        Route::put('/payroll/settings', [AdminPayrollController::class, 'updateSettings'])->name('payroll.settings.update');
        Route::put('/payroll/settings/employees/{employee}', [AdminPayrollController::class, 'updateEmployeeSettings'])->name('payroll.settings.employee.update');
        Route::post('/payroll/deductions', [AdminEmployeePayrollDeductionController::class, 'store'])->name('payroll.deductions.store');
        Route::put('/payroll/deductions/{payrollDeduction}', [AdminEmployeePayrollDeductionController::class, 'update'])->name('payroll.deductions.update');
        Route::delete('/payroll/deductions/{payrollDeduction}', [AdminEmployeePayrollDeductionController::class, 'destroy'])->name('payroll.deductions.destroy');
        Route::get('/payslips/live-sync', [AdminPayslipController::class, 'liveSync'])->name('payslips.live-sync');
        Route::get('/payslips', [AdminPayslipController::class, 'index'])->name('payslips.index');
        Route::get('/payslip-requests/pending', [AdminPayslipController::class, 'pendingRequests'])->name('payslip-requests.pending');
        Route::post('/payslip-requests/{payslipRequest}/status', [AdminPayslipController::class, 'updateRequestStatus'])->name('payslip-requests.status');
        Route::post('/payslip-requests/{payslipRequest}/generate', [AdminPayslipController::class, 'generateAndPublishRequest'])->name('payslip-requests.generate');
        Route::post('/payslips/process', [AdminPayslipController::class, 'process'])->name('payslips.process');
        Route::post('/payslips/{payslip}/approve', [AdminPayslipController::class, 'approve'])->name('payslips.approve');
        Route::post('/payslips/{payslip}/paid', [AdminPayslipController::class, 'markPaid'])->name('payslips.paid');
        Route::get('/visitors', [AdminVisitorAccountController::class, 'index'])->name('visitors.index');
        Route::put('/visitors/profiles/{profile}', [AdminVisitorAccountController::class, 'updateProfile'])->whereNumber('profile')->name('visitors.profiles.update');
        Route::post('/visitors', [AdminVisitorAccountController::class, 'store'])->name('visitors.store');
        Route::put('/visitors/{visitor}', [AdminVisitorAccountController::class, 'update'])->name('visitors.update');
        Route::delete('/visitors/bulk', [AdminVisitorAccountController::class, 'destroySelected'])->name('visitors.bulk-destroy');
        Route::delete('/visitors/{visitor}', [AdminVisitorAccountController::class, 'destroy'])->name('visitors.destroy');
        Route::get('/holidays', [AdminHolidayController::class, 'index'])->name('holidays.index');
        Route::post('/holidays', [AdminHolidayController::class, 'store'])->name('holidays.store');
        Route::delete('/holidays/{holiday}', [AdminHolidayController::class, 'destroy'])->name('holidays.destroy');
        Route::get('/payslips/{payslip}/pdf', [AdminPayslipController::class, 'pdf'])->name('payslips.pdf');
        Route::get('/payslips/{payslip}', [AdminPayslipController::class, 'show'])->name('payslips.show');
        Route::get('employees/{employee}/attendances', [AdminEmployeeController::class, 'attendances'])->name('employees.attendances');
        Route::get('employees/live-sync', [AdminEmployeeController::class, 'liveSync'])->name('employees.live-sync');
        Route::delete('employees/bulk', [AdminEmployeeController::class, 'destroySelected'])->name('employees.bulk-destroy');
        Route::get('logbooks/live-sync', [AdminLogbookController::class, 'liveSync'])->name('logbooks.live-sync');
        Route::get('logbooks/download-format', [AdminLogbookController::class, 'downloadFormat'])->name('logbooks.download-format');
        Route::post('logbooks/import', [AdminLogbookController::class, 'import'])->name('logbooks.import');
        Route::get('logbooks/export', [AdminLogbookController::class, 'export'])->name('logbooks.export');
        Route::get('attendances/live-sync', [AdminAttendanceController::class, 'liveSync'])->name('attendances.live-sync');
        Route::post('attendances/punch', [AdminAttendanceController::class, 'punch'])->name('attendances.punch');
        Route::resource('employees', AdminEmployeeController::class)->parameter('employees', 'employee');
        Route::resource('attendances', AdminAttendanceController::class);
        Route::delete('logbooks/{logbook}/user', [AdminLogbookController::class, 'destroyUser'])->name('logbooks.destroy-user');
        Route::delete('logbooks/bulk', [AdminLogbookController::class, 'destroySelected'])->name('logbooks.bulk-destroy');
        Route::resource('logbooks', AdminLogbookController::class)->parameter('logbooks', 'logbook');
    });

    Route::prefix('employee')->name('employee.')->middleware('role:employee')->group(function (): void {
        Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/live-sync', [EmployeeLiveSyncController::class, 'dashboard'])->name('dashboard.live-sync');
        Route::get('/dashboard/notifications', [EmployeeLiveSyncController::class, 'notifications'])->name('dashboard.notifications');
        Route::post('/dashboard/notifications/read', [EmployeeLiveSyncController::class, 'markNotificationsRead'])->name('dashboard.notifications.read');
        Route::delete('/dashboard/notifications', [EmployeeLiveSyncController::class, 'deleteNotifications'])->name('dashboard.notifications.bulk-delete');
        Route::delete('/dashboard/notifications/{notice}', [EmployeeLiveSyncController::class, 'deleteNotification'])->name('dashboard.notifications.delete');
        Route::get('/profile', [EmployeeProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [EmployeeProfileController::class, 'update'])->name('profile.update');
        Route::get('/attendances', [EmployeeAttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendances/live-sync', [EmployeeLiveSyncController::class, 'attendances'])->name('attendances.live-sync');
        Route::get('/payslips', [EmployeePayslipController::class, 'index'])->name('payslips.index');
        Route::post('/payslips/request', [EmployeePayslipController::class, 'requestPast'])->name('payslips.request');
        Route::get('/payslips/{payslip}/pdf', [EmployeePayslipController::class, 'pdf'])->name('payslips.pdf');
        Route::get('/payslips/{payslip}', [EmployeePayslipController::class, 'show'])->name('payslips.show');
        Route::middleware('logbook.access')->group(function (): void {
            Route::get('/logbooks', [EmployeeLogbookController::class, 'index'])->name('logbooks.index');
            Route::get('/logbooks/live-sync', [EmployeeLiveSyncController::class, 'logbooks'])->name('logbooks.live-sync');
        });
    });
});
