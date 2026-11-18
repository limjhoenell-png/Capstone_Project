<?php

namespace Tests\Feature\Admin;

use App\Models\AnnualBenefit;
use App\Models\Attendance;
use App\Models\EmployeePayrollDeduction;
use App\Models\Payslip;
use App\Models\PayslipRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPayslipTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_employee_past_payslip_requests(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $request = PayslipRequest::query()->create([
            'user_id' => $employee->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payslips.index'))
            ->assertOk()
            ->assertSee('Past Payslip Requests')
            ->assertSee($employee->name)
            ->assertSee('June 2026')
            ->assertSee('Mark in review');

        $this->actingAs($admin)
            ->postJson(route('admin.payslip-requests.status', $request), ['status' => 'reviewing'])
            ->assertOk()
            ->assertJsonPath('pending_count', 0);

        $this->assertDatabaseHas('payslip_requests', [
            'id' => $request->id,
            'status' => 'reviewing',
            'reviewed_by' => $admin->id,
        ]);
        $this->actingAs($admin)
            ->getJson(route('admin.payslip-requests.pending'))
            ->assertOk()
            ->assertJsonPath('requests.0.status', 'reviewing')
            ->assertJsonPath('pending_count', 0);

        $this->actingAs($admin)
            ->post(route('admin.payslip-requests.status', $request), ['status' => 'fulfilled'])
            ->assertRedirect();

        $this->assertDatabaseHas('payslip_requests', [
            'id' => $request->id,
            'status' => 'fulfilled',
        ]);
    }

    public function test_admin_request_modal_lists_pending_items_and_generates_for_the_requested_employee_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $requestedEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800,
        ]);
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $payslipRequest = PayslipRequest::query()->create([
            'user_id' => $requestedEmployee->id,
            'period_start' => '2025-09-01',
            'period_end' => '2025-09-30',
            'status' => 'pending',
        ]);
        foreach (['2025-09-15', '2025-08-31', '2025-10-01'] as $attendanceDate) {
            $date = Carbon::parse($attendanceDate);
            Attendance::query()->create([
                'user_id' => $requestedEmployee->id,
                'attendance_date' => $date->toDateString(),
                'time_in' => $date->copy()->setTime(8, 0),
                'time_out' => $date->copy()->setTime(16, 0),
            ]);
        }

        $this->actingAs($admin)
            ->getJson(route('admin.payslip-requests.pending'))
            ->assertOk()
            ->assertJsonPath('pending_count', 1)
            ->assertJsonPath('requests.0.employee', $requestedEmployee->name)
            ->assertJsonPath('requests.0.period_label', 'September 2025')
            ->assertJsonPath('requests.0.employee_id', $requestedEmployee->id);

        $this->actingAs($admin)
            ->get(route('admin.payroll.index'))
            ->assertOk()
            ->assertSee('id="admin-payslip-request-open"', false)
            ->assertSee('id="admin-payslip-request-modal"', false)
            ->assertSee('Payslip Request')
            ->assertSee('Pending Payslip Requests');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('id="admin-payslip-request-open"', false);

        $this->actingAs($admin)
            ->get(route('admin.payroll.index', [
                'period_start' => '2025-09-01',
                'period_end' => '2025-09-30',
                'employee_id' => $requestedEmployee->id,
            ]))
            ->assertOk()
            ->assertViewHas('employees', fn ($employees): bool => $employees->modelKeys() === [$requestedEmployee->id]);

        $this->actingAs($admin)
            ->post(route('admin.payslip-requests.generate', $payslipRequest))
            ->assertRedirect(route('admin.payslips.index', [
                'period_start' => '2025-09-01',
                'period_end' => '2025-09-30',
                'employee_id' => $requestedEmployee->id,
            ]));

        $payslip = Payslip::query()->where('user_id', $requestedEmployee->id)->firstOrFail();
        $this->assertDatabaseHas('payslips', [
            'id' => $payslip->id,
            'user_id' => $requestedEmployee->id,
            'period_start' => '2025-09-01',
            'period_end' => '2025-09-30',
            'status' => 'approved',
            'days_worked' => 1,
        ]);
        $this->assertDatabaseMissing('payslips', [
            'user_id' => $otherEmployee->id,
            'period_start' => '2025-09-01',
            'period_end' => '2025-09-30',
        ]);
        $this->assertDatabaseHas('payslip_requests', [
            'id' => $payslipRequest->id,
            'status' => 'fulfilled',
        ]);
        $this->assertDatabaseHas('user_notices', [
            'user_id' => $requestedEmployee->id,
            'title' => 'Payslip published',
        ]);
        $this->actingAs($admin)
            ->getJson(route('admin.payslip-requests.pending'))
            ->assertOk()
            ->assertJsonPath('pending_count', 0);
    }

    public function test_admin_can_generate_and_view_payslip(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800.00,
        ]);

        $day = Carbon::parse('2026-05-01');
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => $day->toDateString(),
            'time_in' => $day->copy()->setTime(9, 0),
            'time_out' => $day->copy()->setTime(17, 0),
            'remarks' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payslips.process'), [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ])
            ->assertRedirect(route('admin.payslips.index', [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]));

        $this->assertDatabaseHas('payslips', [
            'user_id' => $employee->id,
        ]);

        $payslip = Payslip::query()->where('user_id', $employee->id)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.payslips.show', $payslip))
            ->assertOk()
            ->assertSee('Gross Pay')
            ->assertSee('Net Pay')
            ->assertSee('@page', false)
            ->assertSee('margin: 0;', false)
            ->assertSee('padding: 10mm', false)
            ->assertDontSee('Total hours recorded');

        $pdfHtml = view('admin.payslips.pdf', ['payslip' => $payslip])->render();
        $this->assertSame(2, substr_count($pdfHtml, 'Official Payslip Document'));
        $this->assertSame(2, substr_count($pdfHtml, 'Authorized Signature'));
        $this->assertStringContainsString('page-break-after: always', $pdfHtml);

        $this->view('admin.payslips.pdf', ['payslip' => $payslip])
            ->assertSee('Official Payslip Document')
            ->assertDontSee('Official Payroll Document')
            ->assertDontSee('This is a computer-generated document.')
            ->assertDontSee('No signature is required unless stated otherwise.')
            ->assertDontSee('Total hours recorded');
    }

    public function test_payroll_generation_creates_payslips_for_all_personnel_roles(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($admin)
            ->post(route('admin.payslips.process'), [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ])
            ->assertRedirect(route('admin.payslips.index', [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]))
            ->assertSessionHas('status', 'Payslips generated for 2 employee(s) for the selected period.');

        foreach ([$admin, $employee] as $person) {
            $this->assertDatabaseHas('payslips', [
                'user_id' => $person->id,
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]);
        }
    }

    public function test_admin_payslips_list_supports_configurable_page_sizes_and_navigation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        for ($index = 1; $index <= 6; $index++) {
            $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
            Payslip::query()->create([
                'user_id' => $employee->id,
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
                'employee_display_name' => sprintf('Pagination Employee %02d', $index),
                'pay_rate_type' => User::PAY_RATE_DAILY,
                'gross_pay' => 100,
                'net_pay' => 100,
            ]);
        }

        $firstPage = $this->actingAs($admin)
            ->get(route('admin.payslips.index', ['per_page' => 5]));

        $firstPage->assertOk()
            ->assertSee('value="5" selected', false)
            ->assertSee('value="10"', false)
            ->assertSee('value="15"', false)
            ->assertSee('value="20"', false)
            ->assertSee('value="25"', false)
            ->assertSee('value="50"', false)
            ->assertSee('value="75"', false)
            ->assertSee('value="100"', false)
            ->assertSee('background: var(--c-surface-2)', false)
            ->assertSee('background: var(--c-primary)', false)
            ->assertDontSee('background: #0f172a', false)
            ->assertSee('Pagination Employee 01')
            ->assertSee('Pagination Employee 05')
            ->assertDontSee('Pagination Employee 06')
            ->assertSee('per_page=5');

        $this->actingAs($admin)
            ->get(route('admin.payslips.index', ['per_page' => 5, 'page' => 2]))
            ->assertOk()
            ->assertSee('Pagination Employee 06')
            ->assertDontSee('Pagination Employee 01');

        $this->actingAs($admin)
            ->get(route('admin.payslips.index'))
            ->assertOk()
            ->assertSee('value="10" selected', false);
    }

    public function test_payslip_net_reflects_configured_deductions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800.00,
        ]);

        EmployeePayrollDeduction::query()->create([
            'user_id' => $employee->id,
            'name' => 'Pag-IBIG (HDMF)',
            'calculation_type' => EmployeePayrollDeduction::CALCULATION_FIXED,
            'value' => 200,
            'sort_order' => 0,
        ]);

        $day = Carbon::parse('2026-05-01');
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => $day->toDateString(),
            'time_in' => $day->copy()->setTime(9, 0),
            'time_out' => $day->copy()->setTime(17, 0),
            'remarks' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payslips.process'), [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ])
            ->assertRedirect();

        $payslip = Payslip::query()->where('user_id', $employee->id)->firstOrFail();

        $this->assertSame('600.00', $payslip->net_pay);
        $this->assertSame('200.00', $payslip->deductions_total);
    }

    public function test_payslip_process_uses_consolidated_attendance_hours(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800.00,
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-02',
            'time_in' => '2026-05-02 08:00:00',
            'time_out' => '2026-05-02 16:00:00',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payslips.process'), [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ])
            ->assertRedirect();

        $payslip = Payslip::query()->where('user_id', $employee->id)->firstOrFail();
        $this->assertSame('8.0000', $payslip->total_hours);
        $this->assertSame('8.0000', $payslip->regular_hours);
        $this->assertSame('800.00', $payslip->gross_pay);
    }

    public function test_payslip_and_printed_view_use_capped_payroll_hours_and_pay(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800,
        ]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-02',
            'time_in' => '2026-05-02 08:00:00',
            'time_out' => '2026-05-02 17:36:00',
        ]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-03',
            'time_in' => '2026-05-03 17:00:00',
            'time_out' => '2026-05-03 23:00:00',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payslips.process'), [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ])
            ->assertRedirect();

        $payslip = Payslip::query()->where('user_id', $employee->id)->firstOrFail();
        $this->assertSame('8.0000', $payslip->total_hours);
        $this->assertSame('8.0000', $payslip->regular_hours);
        $this->assertSame('800.00', $payslip->gross_pay);

        $this->actingAs($admin)
            ->get(route('admin.payslips.show', $payslip))
            ->assertOk()
            ->assertSee('8.00')
            ->assertSee('800.00');

        $this->view('admin.payslips.pdf', ['payslip' => $payslip])
            ->assertSee('8.00')
            ->assertSee('800.00');
    }

    public function test_annual_benefit_is_saved_for_selected_employees_and_added_in_payment_month(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800,
        ]);
        $unselectedEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800,
        ]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-04',
            'time_in' => '2026-05-04 08:00:00',
            'time_out' => '2026-05-04 16:00:00',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payroll.annual-benefits.store'), [
                'benefit_type' => 'Other',
                'name' => 'Annual Wellness Benefit',
                'amount' => 500,
                'payment_date' => '2026-05-15',
                'employee_ids' => [$employee->id],
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ])
            ->assertRedirect(route('admin.payroll.index', [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]));

        $this->assertDatabaseHas('annual_benefits', [
            'name' => 'Annual Wellness Benefit',
            'amount' => '500.00',
            'payment_date' => '2026-05-15',
        ]);
        $this->assertDatabaseHas('annual_benefit_user', [
            'user_id' => $employee->id,
        ]);
        $this->assertDatabaseMissing('annual_benefit_user', [
            'user_id' => $unselectedEmployee->id,
        ]);
        $outsideMonthBenefit = AnnualBenefit::query()->create([
            'name' => 'June Benefit',
            'amount' => 1000,
            'payment_date' => '2026-06-01',
        ]);
        $outsideMonthBenefit->employees()->attach($employee);

        $this->post(route('admin.payslips.process'), [
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
        ])->assertRedirect();

        $payslip = Payslip::query()->where('user_id', $employee->id)->firstOrFail();
        $this->assertSame('1300.00', $payslip->gross_pay);
        $this->assertSame('1300.00', $payslip->net_pay);
        $this->assertSame('Annual Wellness Benefit', $payslip->earning_items[0]['name']);
        $this->assertDatabaseHas('payslips', [
            'user_id' => $unselectedEmployee->id,
            'gross_pay' => '0.00',
        ]);
    }

    public function test_multiple_annual_benefits_are_saved_for_selected_employees_in_one_request(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        Payslip::query()->create([
            'user_id' => $employee->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'regular_pay' => 12000,
            'employee_display_name' => $employee->name,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payroll.annual-benefits.store'), [
                'benefit_items' => [
                    [
                        'benefit_type' => 'Other',
                        'name' => 'Wellness Benefit',
                        'amount' => 500,
                        'payment_date' => '2026-12-15',
                    ],
                    [
                        'benefit_type' => '13Month',
                        'payment_date' => '2026-12-15',
                        'calculation_months' => [1],
                    ],
                ],
                'employee_ids' => [$employee->id],
                'period_start' => '2026-12-01',
                'period_end' => '2026-12-31',
            ])
            ->assertRedirect(route('admin.payroll.index', [
                'period_start' => '2026-12-01',
                'period_end' => '2026-12-31',
            ]));

        $this->assertDatabaseCount('annual_benefits', 2);
        $this->assertDatabaseHas('annual_benefits', [
            'name' => 'Wellness Benefit',
            'amount' => '500.00',
            'payment_date' => '2026-12-15',
        ]);
        $this->assertDatabaseHas('annual_benefits', [
            'name' => '13Month',
            'amount' => '12000.00',
            'payment_date' => '2026-12-15',
        ]);
        $this->assertDatabaseCount('annual_benefit_user', 2);
        $this->assertDatabaseHas('annual_benefit_user', [
            'user_id' => $employee->id,
            'amount' => '12000.00',
        ]);
    }

    public function test_thirteenth_month_benefit_calculates_selected_employee_amounts_from_monthly_payslips(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $firstEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $secondEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $unselectedEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        foreach ([
            [$firstEmployee, '2026-01-01', '2026-01-31', 12000],
            [$firstEmployee, '2026-02-01', '2026-02-28', 18000],
            [$secondEmployee, '2026-02-01', '2026-02-28', 12000],
        ] as [$employee, $periodStart, $periodEnd, $regularPay]) {
            Payslip::query()->create([
                'user_id' => $employee->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'pay_rate_type' => User::PAY_RATE_DAILY,
                'regular_pay' => $regularPay,
                'employee_display_name' => $employee->name,
            ]);
        }

        $previewResponse = $this->actingAs($admin)
            ->getJson(route('admin.payroll.annual-benefits.preview', [
                'payment_date' => '2026-12-15',
                'employee_ids' => [$firstEmployee->id, $secondEmployee->id],
                'calculation_months' => [1, 2],
            ]))
            ->assertOk()
            ->assertJsonPath('total_amount', 27000);
        $previewByEmployee = collect($previewResponse->json('employees'))->keyBy('id');
        $this->assertSame(2, $previewByEmployee[$firstEmployee->id]['divisor']);
        $this->assertSame(15000.0, $previewByEmployee[$firstEmployee->id]['amount']);
        $this->assertSame(1, $previewByEmployee[$secondEmployee->id]['divisor']);
        $this->assertSame(12000.0, $previewByEmployee[$secondEmployee->id]['amount']);

        $this->post(route('admin.payroll.annual-benefits.store'), [
            'benefit_type' => '13Month',
            'payment_date' => '2026-12-15',
            'employee_ids' => [$firstEmployee->id, $secondEmployee->id],
            'calculation_months' => [1, 2],
            'period_start' => '2026-12-01',
            'period_end' => '2026-12-31',
        ])->assertRedirect(route('admin.payroll.index', [
            'period_start' => '2026-12-01',
            'period_end' => '2026-12-31',
        ]));

        $benefitId = AnnualBenefit::query()->where('benefit_type', '13Month')->value('id');
        $this->assertNotNull($benefitId);
        $this->assertDatabaseHas('annual_benefits', [
            'id' => $benefitId,
            'name' => '13Month',
            'amount' => '27000.00',
            'calculation_year' => 2026,
        ]);
        $this->assertDatabaseHas('annual_benefit_user', [
            'annual_benefit_id' => $benefitId,
            'user_id' => $firstEmployee->id,
            'amount' => '15000.00',
        ]);
        $this->assertDatabaseHas('annual_benefit_user', [
            'annual_benefit_id' => $benefitId,
            'user_id' => $secondEmployee->id,
            'amount' => '12000.00',
        ]);
        $this->assertDatabaseMissing('annual_benefit_user', [
            'annual_benefit_id' => $benefitId,
            'user_id' => $unselectedEmployee->id,
        ]);

        $this->post(route('admin.payslips.process'), [
            'period_start' => '2026-12-01',
            'period_end' => '2026-12-31',
        ])->assertRedirect();

        $this->assertSame(
            '15000.00',
            Payslip::query()->where('user_id', $firstEmployee->id)->whereDate('period_start', '2026-12-01')->firstOrFail()->gross_pay
        );
        $this->assertSame(
            '12000.00',
            Payslip::query()->where('user_id', $secondEmployee->id)->whereDate('period_start', '2026-12-01')->firstOrFail()->gross_pay
        );
    }

    public function test_thirteenth_month_preview_accepts_administrator_personnel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Payslip::query()->create([
            'user_id' => $admin->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'regular_pay' => 6000,
            'employee_display_name' => $admin->name,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.payroll.annual-benefits.preview', [
                'payment_date' => '2026-12-15',
                'employee_ids' => [$admin->id],
                'calculation_months' => [1],
            ]))
            ->assertOk()
            ->assertJsonPath('employees.0.id', $admin->id)
            ->assertJsonPath('employees.0.amount', 6000)
            ->assertJsonPath('total_amount', 6000);
    }

    public function test_annual_benefit_modal_has_type_selector_and_13th_month_preview_fields(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.payroll.index'))
            ->assertOk()
            ->assertSee('Benefit Type')
            ->assertSee('<option value="13Month"', false)
            ->assertSee('<option value="Other"', false)
            ->assertSee('13th Month Formula')
            ->assertSee('annual-benefit-calculation-preview', false)
            ->assertSee('name="calculation_months[]"', false);
    }
}
