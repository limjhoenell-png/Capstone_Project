<?php

namespace Tests\Feature\Employee;

use App\Models\Attendance;
use App\Models\Payslip;
use App\Models\PayslipRequest;
use App\Models\User;
use App\Models\UserNotice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_request_a_past_month_payslip_and_cannot_repeat_or_request_future_months(): void
    {
        $this->travelTo(Carbon::create(2026, 11, 5, 12));
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'created_at' => Carbon::create(2025, 1, 15),
            'employment_start_date' => '2025-01-15',
        ]);

        $this->actingAs($employee)
            ->get(route('employee.payslips.index'))
            ->assertOk()
            ->assertSee('Request Past Payslip')
            ->assertSee('type="month"', false)
            ->assertSee('min="2025-01"', false)
            ->assertSee('max="2026-10"', false);

        $this->post(route('employee.payslips.request'), ['period' => '2026-06'])
            ->assertRedirect(route('employee.payslips.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('payslip_requests', [
            'user_id' => $employee->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'status' => 'pending',
        ]);

        $this->from(route('employee.payslips.index'))
            ->post(route('employee.payslips.request'), ['period' => '2026-06'])
            ->assertRedirect(route('employee.payslips.index'))
            ->assertSessionHasErrors('period');

        $this->from(route('employee.payslips.index'))
            ->post(route('employee.payslips.request'), ['period' => '2026-11'])
            ->assertRedirect(route('employee.payslips.index'))
            ->assertSessionHasErrors('period');

        $this->from(route('employee.payslips.index'))
            ->post(route('employee.payslips.request'), ['period' => '2024-12'])
            ->assertRedirect(route('employee.payslips.index'))
            ->assertSessionHasErrors('period');

        $this->assertSame(1, PayslipRequest::query()->count());
    }

    public function test_employee_can_view_legacy_payslips_without_a_status(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $payslip = Payslip::query()->create([
            'user_id' => $employee->id,
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-15',
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'employee_display_name' => 'Legacy employee',
            'status' => null,
        ]);

        $this->actingAs($employee)
            ->get(route('employee.payslips.index'))
            ->assertOk()
            ->assertViewHas('payslips', fn ($payslips): bool => $payslips->contains('id', $payslip->id));
    }

    public function test_dashboard_shows_payroll_notices_in_modal_and_marks_them_read(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $notice = UserNotice::query()->create([
            'user_id' => $employee->id,
            'title' => 'September 2025 payslip published',
            'body' => 'Your payroll update is ready to view.',
        ]);
        UserNotice::query()->create([
            'user_id' => $employee->id,
            'title' => 'August 2025 payslip published',
            'body' => 'An older payroll update.',
            'read_at' => now()->subDay(),
        ]);
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        UserNotice::query()->create([
            'user_id' => $otherEmployee->id,
            'title' => 'Private payroll update',
            'body' => 'This belongs to another employee.',
        ]);

        $this->actingAs($employee)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Notifications &amp; Activity', false)
            ->assertSee('September 2025 payslip published')
            ->assertSee('August 2025 payslip published')
            ->assertDontSee('Private payroll update')
            ->assertSee('data-notification-unread', false)
            ->assertDontSee('class="me-notices"', false);

        $this->actingAs($employee)
            ->getJson(route('employee.dashboard.live-sync'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'September 2025 payslip published');

        $this->actingAs($employee)
            ->postJson(route('employee.dashboard.notifications.read'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notice->fresh()->read_at);
        $this->assertNull($otherEmployee->notices()->firstOrFail()->read_at);
    }

    public function test_dashboard_notifications_are_paginated_and_employee_can_delete_only_owned_notifications(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $notices = collect();

        for ($index = 1; $index <= 11; $index++) {
            $notices->push(UserNotice::query()->create([
                'user_id' => $employee->id,
                'title' => 'Notification '.$index,
                'body' => 'Notification body '.$index,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]));
        }
        $otherNotice = UserNotice::query()->create([
            'user_id' => $otherEmployee->id,
            'title' => 'Private notification',
            'body' => 'Another employee owns this.',
        ]);

        $this->actingAs($employee)
            ->getJson(route('employee.dashboard.notifications'))
            ->assertOk()
            ->assertJsonCount(10, 'notifications')
            ->assertJsonPath('total', 11)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('last_page', 2);

        $this->actingAs($employee)
            ->getJson(route('employee.dashboard.notifications', ['page' => 2]))
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.id', $notices->first()->id);

        $this->actingAs($employee)
            ->deleteJson(route('employee.dashboard.notifications.delete', ['notice' => $otherNotice->id]))
            ->assertNotFound();

        $this->actingAs($employee)
            ->deleteJson(route('employee.dashboard.notifications.bulk-delete'), [
                'ids' => [$notices->last()->id, $otherNotice->id],
            ])
            ->assertUnprocessable();

        $this->actingAs($employee)
            ->deleteJson(route('employee.dashboard.notifications.bulk-delete'), [
                'ids' => [$notices->last()->id],
            ])
            ->assertOk()
            ->assertJsonPath('total', 10);

        $this->assertDatabaseHas('user_notices', ['id' => $otherNotice->id]);
        $this->assertDatabaseMissing('user_notices', ['id' => $notices->last()->id]);
    }

    public function test_published_payslip_notifications_include_owned_view_and_print_links(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $payslip = Payslip::query()->create([
            'user_id' => $employee->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'employee_display_name' => $employee->name,
            'status' => 'approved',
            'kind' => 'regular',
        ]);
        UserNotice::query()->create([
            'user_id' => $employee->id,
            'payslip_id' => $payslip->id,
            'title' => 'Payslip published',
            'body' => 'Your payslip is ready.',
        ]);

        $this->actingAs($employee)
            ->getJson(route('employee.dashboard.notifications'))
            ->assertOk()
            ->assertJsonPath('notifications.0.view_url', route('employee.payslips.show', $payslip))
            ->assertJsonPath('notifications.0.print_url', route('employee.payslips.pdf', $payslip));
    }

    public function test_employee_payslips_support_page_sizes_and_navigation(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        for ($month = 1; $month <= 12; $month++) {
            $periodStart = sprintf('2026-%02d-01', $month);
            $periodEnd = date('Y-m-t', strtotime($periodStart));
            Payslip::query()->create([
                'user_id' => $employee->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'pay_rate_type' => User::PAY_RATE_DAILY,
                'employee_display_name' => sprintf('Employee Payslip %02d', $month),
                'status' => 'approved',
            ]);
        }

        $firstPage = $this->actingAs($employee)->get(route('employee.payslips.index'));
        $firstPage->assertOk()
            ->assertSee('Rows per page')
            ->assertSee('value="10" selected', false)
            ->assertSee('value="15"', false)
            ->assertSee('value="20"', false)
            ->assertSee('value="30"', false)
            ->assertSee('Employee Payslip 12')
            ->assertSee('Employee Payslip 03')
            ->assertDontSee('Employee Payslip 02')
            ->assertSee('of 12 payslips')
            ->assertSee('per_page=10');

        $this->get(route('employee.payslips.index', ['per_page' => 10, 'page' => 2]))
            ->assertOk()
            ->assertSee('Employee Payslip 02')
            ->assertDontSee('Employee Payslip 12');

        $this->get(route('employee.payslips.index', ['per_page' => 15]))
            ->assertOk()
            ->assertSee('value="15" selected', false)
            ->assertSee('Employee Payslip 01');
    }

    public function test_employee_attendance_supports_page_sizes_and_navigation(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        for ($day = 1; $day <= 12; $day++) {
            $date = Carbon::today()->subDays($day);
            Attendance::query()->create([
                'user_id' => $employee->id,
                'attendance_date' => $date->toDateString(),
                'time_in' => $date->copy()->setTime(8, 0),
                'time_out' => $date->copy()->setTime(16, 0),
            ]);
        }

        $firstPage = $this->actingAs($employee)->get(route('employee.attendances.index'));
        $firstPage->assertOk()
            ->assertSee('Rows per page')
            ->assertSee('value="10" selected', false)
            ->assertSee('value="15"', false)
            ->assertSee('value="20"', false)
            ->assertSee('value="30"', false)
            ->assertSee('attendance_page=2')
            ->assertDontSee('Days Worked This Month')
            ->assertDontSee('Hours Worked This Month')
            ->assertDontSee('Late Days This Month')
            ->assertDontSee('Monthly Absences')
            ->assertDontSee('Past year, one row per day')
            ->assertDontSee('One consolidated attendance record per day.')
            ->assertDontSee('Showing 1–10 of 12 days');

        $this->get(route('employee.attendances.index', ['per_page' => 10, 'attendance_page' => 2]))
            ->assertOk()
            ->assertSee('Daily attendance')
            ->assertSee('Rows per page');

        $this->get(route('employee.attendances.index', ['per_page' => 15]))
            ->assertOk()
            ->assertSee('value="15" selected', false);
    }

    public function test_dashboard_recent_days_paginates_by_week_and_resets_each_month(): void
    {
        $this->travelTo(Carbon::create(2026, 11, 30, 12));
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        for ($day = 1; $day <= 16; $day++) {
            $date = Carbon::create(2026, 11, $day);
            Attendance::query()->create([
                'user_id' => $employee->id,
                'attendance_date' => $date->toDateString(),
                'time_in' => $date->copy()->setTime(8, 0),
                'time_out' => $date->copy()->setTime(16, 0),
                'remarks' => sprintf('Dashboard day %02d', $day),
            ]);
        }

        $this->actingAs($employee)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Week 1')
            ->assertSee('Week 2')
            ->assertSee('Week 3')
            ->assertSee('Dashboard day 16')
            ->assertDontSee('Dashboard day 09')
            ->assertSee('week_month=2026-11&amp;week_page=2', false);

        $this->get(route('employee.dashboard', ['week_month' => '2026-11', 'week_page' => 2]))
            ->assertOk()
            ->assertSee('aria-current="page">Week 2', false)
            ->assertSee('Dashboard day 09')
            ->assertDontSee('Dashboard day 16');

        $this->get(route('employee.dashboard', ['week_month' => '2026-10', 'week_page' => 3]))
            ->assertOk()
            ->assertSee('aria-current="page">Week 1', false)
            ->assertSee('Dashboard day 16');
    }

    public function test_employee_can_view_employee_pages(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $dashboardResponse = $this->actingAs($employee)->get(route('employee.dashboard'));
        $dashboardResponse->assertOk()
            ->assertSee('Open menu', false)
            ->assertSee('id="app-sidebar"', false)
            ->assertSee('id="employee-password-modal"', false)
            ->assertSee(route('password.change'), false)
            ->assertSee('Update password')
            ->assertSee('Days Worked This Month')
            ->assertSee('Payable Hours This Month')
            ->assertSee('Late Days This Month')
            ->assertSee('Monthly Absences')
            ->assertDontSee('Your attendance and payslips are available from the sidebar.')
            ->assertDontSee('Entries linked to you')
            ->assertDontSee('Latest out')
            ->assertDontSee('>Password</a>', false)
            ->assertSee('aria-label="Breadcrumb"', false);
        self::assertSame(3, substr_count($dashboardResponse->getContent(), 'data-password-visibility'));

        $this->actingAs($employee)
            ->get(route('employee.attendances.index'))
            ->assertOk();

        $this->actingAs($employee)
            ->get(route('employee.profile.edit'))
            ->assertOk()
            ->assertSee('Edit your profile', false)
            ->assertSee('data-open-password-modal', false)
            ->assertSee('aria-controls="employee-password-modal"', false)
            ->assertSee('Change password');

        $logbooksResponse = $this->actingAs($employee)
            ->get(route('employee.logbooks.index'));
        $logbooksResponse->assertOk();
        $logbooksHtml = $logbooksResponse->getContent();
        $this->assertTrue(
            str_contains($logbooksHtml, '<table')
                || str_contains($logbooksHtml, '<TABLE')
                || str_contains($logbooksHtml, 'No logbook storage tables'),
            'Employee logbook page should render a table or an explicit empty-storage notice.'
        );

        $dashboardSync = $this->actingAs($employee)
            ->getJson(route('employee.dashboard.live-sync'));
        $dashboardSync->assertOk()
            ->assertJsonStructure(['changed', 'signature', 'html'])
            ->assertJsonPath('changed', true);
        $this->assertStringContainsString('Days Worked This Month', $dashboardSync->json('html'));
        $this->assertStringContainsString('Payable Hours This Month', $dashboardSync->json('html'));
        $this->assertStringContainsString('Late Days This Month', $dashboardSync->json('html'));
        $this->assertStringContainsString('Monthly Absences', $dashboardSync->json('html'));

        $this->actingAs($employee)
            ->getJson(route('employee.attendances.live-sync'))
            ->assertOk()
            ->assertJsonStructure(['changed', 'signature']);

        $this->actingAs($employee)
            ->getJson(route('employee.logbooks.live-sync'))
            ->assertOk()
            ->assertJsonPath('changed', true);
    }

    public function test_employee_can_update_password_from_modal_json_submission(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'password' => 'current-password',
        ]);

        $this->actingAs($employee)
            ->putJson(route('password.change'), [
                'current_password' => 'incorrect-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->actingAs($employee)
            ->putJson(route('password.change'), [
                'current_password' => 'current-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->actingAs($employee)
            ->putJson(route('password.change'), [
                'current_password' => 'current-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Password updated.');

        $this->assertTrue(Hash::check('new-secure-password', $employee->fresh()->password));
    }

    public function test_employee_logbook_tables_have_independent_page_size_selectors(): void
    {
        Schema::create('logbook', function ($table): void {
            $table->increments('logbook_id');
            $table->string('full_name')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('purpose_of_transaction')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->dateTime('time_in')->nullable();
        });

        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $response = $this->actingAs($employee)->get(route('employee.logbooks.index'));

        $response->assertOk()
            ->assertSee('name="accounts_per_page"', false)
            ->assertSee('name="legacy_per_page"', false)
            ->assertSee('data-logbook-controls="employee-logbook-accounts"', false)
            ->assertSee('data-logbook-controls="employee-legacy-logbook"', false)
            ->assertSee('Logbook Transactions')
            ->assertSee('id="employee-logbook-accounts"', false)
            ->assertSee('id="employee-legacy-logbook"', false)
            ->assertSee('data-logbook-page-size', false)
            ->assertDontSee('onchange="this.form.submit()"', false);

        foreach ([5, 10, 15, 20, 25, 50, 75, 100] as $pageSize) {
            $response->assertSee('value="'.$pageSize.'"', false);
        }
    }

    public function test_employee_can_update_only_their_own_profile_fields(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $other = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($employee)
            ->put(route('employee.profile.update'), [
                'name' => 'Updated Employee',
                'email' => $employee->email,
                'contact_number' => '555-0101',
                'position' => 'Clerk',
                'department' => 'Records',
            ])
            ->assertRedirect(route('employee.profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'name' => 'Updated Employee',
            'full_name' => 'Updated Employee',
            'contact_number' => '555-0101',
        ]);
        $this->assertDatabaseHas('users', ['id' => $other->id, 'name' => $other->name]);
    }

    public function test_leave_routes_are_not_available(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($employee)->get('/employee/leaves')->assertNotFound();
    }

    public function test_employee_cannot_access_admin_routes_or_perform_crud(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($employee)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($employee)
            ->post(route('admin.employees.store'), [
                'name' => 'Blocked',
                'email' => 'blocked@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_EMPLOYEE,
            ])
            ->assertForbidden();
    }
}
