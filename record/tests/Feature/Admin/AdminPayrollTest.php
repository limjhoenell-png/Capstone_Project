<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\EmployeePayrollDeduction;
use App\Models\PayrollPrintSetting;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_payroll_and_totals_use_attendance_hours(): void
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
            ->get(route('admin.payroll.index', [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertSee($employee->name)
            ->assertSee('>Payable Hours</button>', false)
            ->assertSee('Create Annual Benefits')
            ->assertSee(route('admin.payroll.annual-benefits.create', [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]), false)
            ->assertDontSee('annual-benefit-dialog')
            ->assertSee('Customize Print Layout')
            ->assertDontSee('13th month year')
            ->assertDontSee('Create 13th month drafts')
            ->assertDontSee('Hours (Reg / OT)')
            ->assertSee(route('admin.payroll.breakdown', [
                'employee' => $employee,
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]), false)
            ->assertSee('800.00');
    }

    public function test_payroll_preview_and_rate_settings_include_administrators_without_a_preset_rate(): void
    {
        $admin = User::factory()->create([
            'name' => 'Payroll Administrator',
            'role' => User::ROLE_ADMIN,
        ]);
        $secondAdmin = User::factory()->create([
            'name' => 'Second Administrator',
            'role' => User::ROLE_ADMIN,
        ]);
        $employee = User::factory()->create([
            'name' => 'Roster Employee',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payroll.index'))
            ->assertOk()
            ->assertSee($admin->name)
            ->assertSee($secondAdmin->name)
            ->assertSee($employee->name)
            ->assertSee('Set rate in payroll settings')
            ->assertViewHas('lines', fn (array $lines): bool => count($lines) === 3);

        $this->get(route('admin.payroll.index', ['employee_id' => $secondAdmin->id]))
            ->assertOk()
            ->assertSee($secondAdmin->name)
            ->assertViewHas('employees', fn ($employees): bool => $employees->modelKeys() === [$secondAdmin->id]);

        $this->get(route('admin.payroll.settings', ['employee' => $secondAdmin->id]))
            ->assertOk()
            ->assertSee('Second Administrator')
            ->assertSee('employee-payroll-'.$secondAdmin->id, false);
    }

    public function test_admin_can_open_the_annual_benefit_creation_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $response = $this->actingAs($admin)
            ->get(route('admin.payroll.annual-benefits.create', [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]))
            ->assertOk();

        $response
            ->assertSee('Create Annual Benefits')
            ->assertSee($employee->name)
            ->assertSee('Benefit Name')
            ->assertSee('Select All')
            ->assertSee('Deselect All')
            ->assertSee('name="period_start" value="2026-05-01"', false)
            ->assertSee('name="period_end" value="2026-05-31"', false)
            ->assertDontSee('<dialog', false);

        self::assertSame(1, substr_count($response->getContent(), 'Back to Payroll'));
    }

    public function test_admin_can_save_uppercase_payroll_print_layout_and_use_it_in_report(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)
            ->post(route('admin.payroll.print-layout.update'), [
                'barangay_name' => 'barangay bagong',
                'city_name' => 'digos city',
                'report_title' => 'monthly payroll',
                'source_fund' => 'general fund',
                'payroll_number_format' => 'pay-{year}-{month}',
                'period_covered_label' => 'period covered',
                'payroll_number_label' => 'payroll number',
                'source_fund_label' => 'source of fund',
                'date_prepared_label' => 'date prepared',
                'prepared_date_description' => 'prepared on {date}',
                'punong_barangay_name' => 'juan dela cruz',
                'punong_barangay_designation' => 'punong barangay',
                'appropriations_chair_name' => 'maria santos',
                'appropriations_chair_designation' => 'chairman committee on appropriations',
                'treasurer_name' => 'pedro reyes',
                'treasurer_designation' => 'barangay treasurer',
                'approved_by_name' => 'ana cruz',
                'approved_by_designation' => 'approved by',
                'prepared_by_name' => 'luis garcia',
                'prepared_by_designation' => 'prepared by',
            ])
            ->assertRedirect(route('admin.payroll.index'));

        $printSettings = PayrollPrintSetting::query()->firstOrFail();
        $this->assertSame('BARANGAY BAGONG', $printSettings->barangay_name);
        $this->assertSame('JUAN DELA CRUZ', $printSettings->punong_barangay_name);
        $this->assertSame('PAY-{YEAR}-{MONTH}', $printSettings->payroll_number_format);

        $this->get(route('admin.payroll.print', ['period' => '2026-05']))
            ->assertOk()
            ->assertSee('BARANGAY BAGONG')
            ->assertSee('MONTHLY PAYROLL')
            ->assertSee('PAY-2026-05')
            ->assertSee('GENERAL FUND')
            ->assertSee('JUAN DELA CRUZ')
            ->assertSee('CHAIRMAN COMMITTEE ON APPROPRIATIONS')
            ->assertSee('PREPARED ON');
    }

    public function test_admin_can_print_monthly_payroll_report_with_live_employee_totals(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Dynamic Payroll Employee',
            'position' => 'Barangay Clerk',
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
            ->get(route('admin.payroll.print', [
                'period' => '2026-05',
                'source_fund' => 'General Fund',
            ]))
            ->assertOk()
            ->assertSee('Barangay San Jose')
            ->assertSee('Digos City')
            ->assertSee('May 1, 2026')
            ->assertSee('PAY-202605')
            ->assertSee('General Fund')
            ->assertSee('Dynamic Payroll Employee')
            ->assertSee('Barangay Clerk')
            ->assertSee('800.00')
            ->assertSee('Punong Barangay')
            ->assertSee('Chairman Committee on Appropriations')
            ->assertSee('Barangay Treasurer')
            ->assertSee('Approved By')
            ->assertSee('Prepared By');
    }

    public function test_payable_hours_are_capped_at_eight_without_overtime_multiplier(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_HOURLY,
            'pay_rate' => 100,
            'hours_per_day' => 12,
        ]);
        $settings = PayrollSetting::current();
        $settings->update(['overtime_multiplier' => 3]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-01',
            'time_in' => '2026-05-01 08:00:00',
            'time_out' => '2026-05-01 18:00:00',
        ]);

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-01'),
            $settings
        );

        $this->assertSame(8.0, $quote['split']['regular_hours']);
        $this->assertSame(0.0, $quote['split']['overtime_hours']);
        $this->assertSame(800.0, $quote['regular_pay']);
        $this->assertSame(0.0, $quote['overtime_pay']);
        $this->assertSame(800.0, $quote['gross_pay']);
    }

    public function test_daily_rate_pay_uses_the_standard_eight_hour_basis(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 1000,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.payroll.settings.employee.update', $employee), [
                'pay_rate_type' => User::PAY_RATE_DAILY,
                'pay_rate' => 1000,
                'hours_per_day' => 4,
                'monthly_incentive' => 0,
                'working_days_present' => 1,
                'working_days' => ['1', '2', '3', '4', '5'],
                'employee_rest_days_present' => 1,
                'employee_rest_days' => ['6', '7'],
            ])
            ->assertRedirect(route('admin.payroll.settings', ['employee' => $employee->id]).'#employee-payroll-'.$employee->id);

        $employee->refresh();
        $this->assertSame('4.00', $employee->hours_per_day);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-01',
            'time_in' => '2026-05-01 08:00:00',
            'time_out' => '2026-05-01 16:00:00',
        ]);

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-01'),
            PayrollSetting::current()
        );

        $this->assertSame(2.0, $quote['equivalent_days']);
        $this->assertSame(1000.0, $quote['gross_pay']);
    }

    public function test_daily_rate_is_prorated_by_rendered_minutes_and_invalid_time_in_is_absent(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800,
        ]);

        foreach ([
            ['2026-05-01', '05:00:00', '09:00:00'],
            ['2026-05-02', '16:59:59', '17:29:59'],
            ['2026-05-03', '04:59:59', '12:00:00'],
            ['2026-05-04', '17:00:00', '23:00:00'],
        ] as [$date, $timeIn, $timeOut]) {
            Attendance::query()->create([
                'user_id' => $employee->id,
                'attendance_date' => $date,
                'time_in' => $date.' '.$timeIn,
                'time_out' => $date.' '.$timeOut,
            ]);
        }

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-04'),
            PayrollSetting::current()
        );

        $this->assertSame(4.5, $quote['split']['total_hours']);
        $this->assertSame(4.5, $quote['split']['regular_hours']);
        $this->assertSame(2, $quote['split']['days_worked']);
        $this->assertSame(450.0, $quote['gross_pay']);
        $this->assertSame([false, false], array_values(array_filter(
            array_column($quote['split']['days'], 'valid_time_in'),
            fn (bool $valid, int $index): bool => $index >= 2,
            ARRAY_FILTER_USE_BOTH
        )));

        $invalidQuote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-03'),
            Carbon::parse('2026-05-04'),
            PayrollSetting::current()
        );

        $this->assertSame(0.0, $invalidQuote['split']['total_hours']);
        $this->assertSame(0.0, $invalidQuote['split']['regular_hours']);
        $this->assertSame(0.0, $invalidQuote['gross_pay']);
    }

    public function test_hourly_pay_is_prorated_to_minutes_and_capped_at_eight_hours(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_HOURLY,
            'pay_rate' => 80,
        ]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-01',
            'time_in' => '2026-05-01 08:00:00',
            'time_out' => '2026-05-01 08:30:00',
        ]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-02',
            'time_in' => '2026-05-02 08:00:00',
            'time_out' => '2026-05-02 17:36:00',
        ]);

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-02'),
            PayrollSetting::current()
        );

        $this->assertSame(8.5, $quote['split']['regular_hours']);
        $this->assertSame(680.0, $quote['gross_pay']);
        $this->assertSame(8.0, $quote['split']['days'][1]['hours']);
    }

    public function test_hourly_pay_rounds_currency_after_exact_minute_calculation(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_HOURLY,
            'pay_rate' => 80,
        ]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-01',
            'time_in' => '2026-05-01 08:00:00',
            'time_out' => '2026-05-01 08:01:00',
        ]);

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-01'),
            PayrollSetting::current()
        );

        $this->assertSame(0.0167, $quote['split']['regular_hours']);
        $this->assertSame(1.33, $quote['gross_pay']);
    }

    public function test_payroll_summary_and_breakdown_show_capped_payable_hours(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800,
        ]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-01',
            'time_in' => '2026-05-01 08:00:00',
            'time_out' => '2026-05-01 17:36:00',
        ]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-02',
            'time_in' => '2026-05-02 17:00:00',
            'time_out' => '2026-05-02 23:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.payroll.index', [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-02',
                'employee_id' => $employee->id,
            ]))
            ->assertOk()
            ->assertSee('8.00')
            ->assertSee('800.00')
            ->assertViewHas('lines', function (array $lines) use ($employee): bool {
                $line = collect($lines)->first(
                    fn (array $row): bool => $row['employee']->is($employee)
                );

                return $line !== null
                    && $line['total_hours'] === 8.0
                    && $line['regular_hours'] === 8.0
                    && $line['gross_pay'] === 800.0;
            });

        $this->get(route('admin.payroll.breakdown', [
            'employee' => $employee,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-02',
        ]))
            ->assertOk()
            ->assertSee('8.00')
            ->assertSee('Absent — Time-In outside valid window');
    }

    public function test_hourly_employee_daily_hours_limit_caps_billable_hours_before_pay(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_HOURLY,
            'pay_rate' => 50,
            'hours_per_day' => 5,
        ]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-01',
            'time_in' => '2026-05-01 08:00:00',
            'time_out' => '2026-05-01 15:00:00',
        ]);

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-01'),
            PayrollSetting::current()
        );

        $this->assertSame(7.0, $quote['split']['attendance_hours']);
        $this->assertSame(5.0, $quote['split']['regular_hours']);
        $this->assertSame(250.0, $quote['gross_pay']);
    }

    public function test_payroll_preview_hours_are_limited_to_valid_attendance_in_selected_period(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_HOURLY,
            'pay_rate' => 100,
        ]);

        foreach ([
            ['2026-10-31', '08:00:00', '16:00:00'],
            ['2026-11-01', '08:00:00', '15:30:00'],
            ['2026-11-10', '08:00:00', null],
            ['2026-11-30', '08:00:00', '17:00:00'],
            ['2026-12-01', '08:00:00', '16:00:00'],
        ] as [$date, $timeIn, $timeOut]) {
            Attendance::query()->create([
                'user_id' => $employee->id,
                'attendance_date' => $date,
                'time_in' => $date.' '.$timeIn,
                'time_out' => $timeOut === null ? null : $date.' '.$timeOut,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.payroll.index', [
                'period_start' => '2026-11-01',
                'period_end' => '2026-11-30',
            ]))
            ->assertOk()
            ->assertSee('data-sort-hours="16.5"', false)
            ->assertSee('16.50');

        $this->get(route('admin.payroll.index', [
            'period_start' => '2026-12-01',
            'period_end' => '2026-12-31',
        ]))
            ->assertOk()
            ->assertSee('data-sort-hours="8"', false)
            ->assertSee('8.00');
    }

    public function test_admin_can_view_employee_daily_attendance_breakdown(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-01',
            'time_in' => '2026-05-01 09:00:00',
            'time_out' => '2026-05-01 18:00:00',
            'remarks' => 'Front desk shift',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payroll.breakdown', [
                'employee' => $employee,
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertSee('Daily Attendance Breakdown')
            ->assertSee('Attendance Records')
            ->assertSee('May 1, 2026')
            ->assertSee('9:00 AM')
            ->assertSee('6:00 PM')
            ->assertSee('Attendance record #'.$attendance->id)
            ->assertSee('Front desk shift')
            ->assertSee('1.00');
    }

    public function test_admin_can_update_payroll_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $settings = PayrollSetting::current();
        $settings->update([
            'overtime_multiplier' => 1.75,
            'rest_day_multiplier' => 1.5,
            'regular_holiday_multiplier' => 2.25,
            'special_holiday_multiplier' => 1.4,
            'apply_statutory' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.payroll.settings.update'), [
                'shift_start' => '08:00',
                'shift_end' => '17:00',
                'grace_minutes' => 20,
                'late_penalty_increment_minutes' => 5,
                'late_penalty_amount' => 50,
            ])
            ->assertRedirect(route('admin.payroll.settings'));

        $this->assertDatabaseHas('payroll_settings', [
            'id' => $settings->id,
            'shift_start' => '08:00:00',
            'shift_end' => '17:00:00',
            'grace_minutes' => 20,
            'late_penalty_increment_minutes' => 5,
            'late_penalty_amount' => '50.00',
            'overtime_multiplier' => '1.75',
            'rest_day_multiplier' => '1.50',
            'regular_holiday_multiplier' => '2.25',
            'special_holiday_multiplier' => '1.40',
            'apply_statutory' => true,
        ]);
    }

    public function test_payroll_settings_hide_night_differential_and_use_eight_am_shift_start(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.payroll.settings'))
            ->assertOk()
            ->assertSee('Global Attendance Controls')
            ->assertSee('Attendance Settings')
            ->assertSee('value="08:00"', false)
            ->assertSee('value="17:00"', false)
            ->assertSee('Grace Minutes')
            ->assertSee('Late Penalty Threshold / Increment (Minutes)')
            ->assertSee('Deduction Amount (PHP)')
            ->assertDontSee('Hours / Full Day')
            ->assertDontSee('Configured Employees')
            ->assertDontSee('Hours per Full Day')
            ->assertSee('Shift Start')
            ->assertSee('Shift End')
            ->assertDontSee('Deduction Rules')
            ->assertDontSee('Rest Days')
            ->assertDontSee('Sunday is the designated rest day')
            ->assertDontSee('Overtime and Premium Settings')
            ->assertDontSee('Overtime Multiplier')
            ->assertDontSee('OT Multiplier')
            ->assertDontSee('Rest Day Multiplier')
            ->assertDontSee('Regular Holiday Multiplier')
            ->assertDontSee('Special Holiday Multiplier')
            ->assertDontSee('Apply SSS, PhilHealth, Pag-IBIG, and withholding tax')
            ->assertDontSee('Night Differential')
            ->assertDontSee('Night Diff Starts')
            ->assertDontSee('Night Diff Ends');
    }

    public function test_payroll_settings_activity_history_shows_five_items_per_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->count(6)->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800,
        ]);

        $firstPage = $this->actingAs($admin)
            ->get(route('admin.payroll.settings'))
            ->assertOk()
            ->assertSee('Payroll Settings Activity History')
            ->assertSee('Showing 1–5 of 7 activities')
            ->assertSee('activity_page=2', false);
        $this->assertSame(5, substr_count($firstPage->getContent(), 'class="payroll-activity-item"'));

        $secondPage = $this->get(route('admin.payroll.settings', ['activity_page' => 2]))
            ->assertOk()
            ->assertSee('Showing 6–7 of 7 activities')
            ->assertSee('Previous')
            ->assertSee('Next');
        $this->assertSame(2, substr_count($secondPage->getContent(), 'class="payroll-activity-item"'));
    }

    public function test_employee_payroll_settings_page_hides_global_summary_and_controls(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_id' => 'TEST-EMPLOYEE-ID',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.payroll.settings', ['employee' => $employee->id]))
            ->assertOk()
            ->assertSee('Employee Payroll Settings')
            ->assertSee('HOURS PER DAY (EQUIVALENT DAY BASIS)')
            ->assertSee('data-hourly-label="DAILY WORK HOURS LIMIT (MAX HOURS/DAY)"', false)
            ->assertSee('Monthly Incentives')
            ->assertSee('Employee Deductions')
            ->assertSee('class="card employee-payroll-card"', false)
            ->assertSee('class="employee-deductions-section"', false)
            ->assertSee('Deductions Rule Builder')
            ->assertSee('ADD DEDUCTION RULE')
            ->assertSee('>NAME</label>', false)
            ->assertSee('>TYPE</label>', false)
            ->assertSee('>VALUE</label>', false)
            ->assertDontSee('<th>NAME</th>', false)
            ->assertDontSee('<th>TYPE</th>', false)
            ->assertDontSee('<th>VALUE</th>', false)
            ->assertDontSee('ID TEST-EMPLOYEE-ID')
            ->assertDontSee('Payroll Settings Activity History')
            ->assertDontSee('Hours / Full Day')
            ->assertDontSee('Configured Employees')
            ->assertDontSee('Deduction Rules')
            ->assertDontSee('Global Attendance Controls')
            ->assertDontSee('Attendance Settings')
            ->assertDontSee('Save Global Settings');

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'Working Days'), strpos($html, 'Employee Payroll Settings'));
        $this->assertLessThan(strpos($html, 'Monthly Incentives'), strpos($html, 'Employee Payroll Settings'));
        $this->assertLessThan(strpos($html, 'Monthly Incentives'), strpos($html, 'HOURS PER DAY (EQUIVALENT DAY BASIS)'));
        $this->assertLessThan(strpos($html, 'Working Days'), strpos($html, 'Monthly Incentives'));
        $this->assertLessThan(strpos($html, 'Rest Days'), strpos($html, 'Working Days'));
        $this->assertLessThan(strpos($html, 'Employee Deductions'), strpos($html, 'Rest Days'));
        $this->assertLessThan(strpos($html, 'Save Employee Settings'), strpos($html, 'Employee Deductions'));
        $this->assertStringContainsString(
            'form="employee-payroll-'.$employee->id.'"',
            $html
        );
    }

    public function test_late_penalty_starts_after_grace_and_charges_each_started_increment(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_HOURLY,
            'pay_rate' => 100,
            'hours_per_day' => 8,
        ]);
        $settings = PayrollSetting::current();
        $settings->update([
            'shift_start' => '08:00:00',
            'grace_minutes' => 15,
            'late_penalty_increment_minutes' => 5,
            'late_penalty_amount' => 50,
        ]);

        foreach ([
            ['date' => '2026-05-01', 'time_in' => '08:15:00'],
            ['date' => '2026-05-02', 'time_in' => '08:16:00'],
            ['date' => '2026-05-03', 'time_in' => '08:21:00'],
        ] as $attendance) {
            Attendance::query()->create([
                'user_id' => $employee->id,
                'attendance_date' => $attendance['date'],
                'time_in' => $attendance['date'].' '.$attendance['time_in'],
                'time_out' => $attendance['date'].' 17:00:00',
            ]);
        }

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-03'),
            $settings
        );

        $this->assertSame(150.0, $quote['late_penalty_total']);
        $this->assertSame(150.0, $quote['deductions_total']);
        $this->assertSame(2250.0, $quote['net_pay']);
    }

    public function test_employee_schedule_settings_save_working_and_rest_days(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($admin)
            ->put(route('admin.payroll.settings.employee.update', $employee), [
                'pay_rate_type' => User::PAY_RATE_DAILY,
                'pay_rate' => 800,
                'monthly_incentive' => 1250.50,
                'working_days_present' => 1,
                'working_days' => ['1', '2', '3', '4', '5'],
                'employee_rest_days_present' => 1,
                'employee_rest_days' => ['6', '7'],
            ])
            ->assertRedirect(route('admin.payroll.settings', ['employee' => $employee->id]).'#employee-payroll-'.$employee->id);

        $employee->refresh();
        $this->assertSame([1, 2, 3, 4, 5], $employee->working_days);
        $this->assertSame([6, 7], $employee->employee_rest_days);
        $this->assertSame('1250.50', $employee->monthly_incentive);
    }

    public function test_employee_rest_days_override_global_rest_days_for_payroll(): void
    {
        $settings = PayrollSetting::current();
        $settings->update(['rest_day_multiplier' => 1.5]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800,
            'working_days' => [1, 2, 3, 4, 5, 6],
            'employee_rest_days' => [7],
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-03',
            'time_in' => '2026-05-03 08:00:00',
            'time_out' => '2026-05-03 16:00:00',
        ]);

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-03'),
            Carbon::parse('2026-05-03'),
            $settings
        );

        $this->assertSame(400.0, $quote['rest_day_pay']);
    }

    public function test_overnight_attendance_does_not_receive_night_differential_pay(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_HOURLY,
            'pay_rate' => 100,
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-04',
            'time_in' => '2026-05-04 22:00:00',
            'time_out' => '2026-05-05 06:00:00',
        ]);

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-04'),
            Carbon::parse('2026-05-04')
        );

        $this->assertSame(0.0, $quote['night_diff_hours']);
        $this->assertSame(0.0, $quote['night_diff_pay']);
        $this->assertSame(0.0, $quote['split']['total_hours']);
        $this->assertSame(0.0, $quote['gross_pay']);
    }

    public function test_payroll_net_reflects_saved_deductions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800.00,
        ]);

        EmployeePayrollDeduction::query()->create([
            'user_id' => $employee->id,
            'name' => 'PhilHealth',
            'calculation_type' => EmployeePayrollDeduction::CALCULATION_FIXED,
            'value' => 100,
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
            ->get(route('admin.payroll.index', [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertSee('PhilHealth')
            ->assertSee('100.00')
            ->assertSee('700.00');
    }

    public function test_admin_can_manage_deductions_on_settings_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $deduction = EmployeePayrollDeduction::query()->create([
            'user_id' => $employee->id,
            'name' => 'SSS (Social Security System)',
            'calculation_type' => EmployeePayrollDeduction::CALCULATION_FIXED,
            'value' => 250,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payroll.deductions.store'), [
                'user_id' => $employee->id,
                'name' => 'SSS (Social Security System)',
                'calculation_type' => EmployeePayrollDeduction::CALCULATION_FIXED,
                'value' => '250.50',
            ])
            ->assertRedirect(route('admin.payroll.settings', ['employee' => $employee->id]).'#employee-deductions-'.$employee->id);

        $createdDeduction = EmployeePayrollDeduction::query()
            ->where('user_id', $employee->id)
            ->where('calculation_type', EmployeePayrollDeduction::CALCULATION_FIXED)
            ->orderByDesc('id')
            ->firstOrFail();
        $this->assertSame('250.50', $createdDeduction->value);

        $this->get(route('admin.payroll.settings', ['employee' => $employee->id]))
            ->assertOk()
            ->assertSee('value="250.50" step="0.01"', false);

        $this->actingAs($admin)
            ->put(route('admin.payroll.deductions.update', $deduction), [
                'name' => 'SSS (Social Security System)',
                'calculation_type' => EmployeePayrollDeduction::CALCULATION_PERCENT_GROSS,
                'value' => 5,
            ])
            ->assertRedirect(route('admin.payroll.settings', ['employee' => $employee->id]).'#employee-deductions-'.$employee->id);
        $this->assertSame('5.00', $deduction->fresh()->value);

        $this->actingAs($admin)
            ->delete(route('admin.payroll.deductions.destroy', $deduction->fresh()))
            ->assertRedirect(route('admin.payroll.settings', ['employee' => $employee->id]).'#employee-deductions-'.$employee->id);

        $this->assertDatabaseMissing('employee_payroll_deductions', [
            'id' => $deduction->id,
        ]);
    }

    public function test_deduction_values_reject_more_than_two_decimal_places(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $deduction = EmployeePayrollDeduction::query()->create([
            'user_id' => $employee->id,
            'name' => 'SSS',
            'calculation_type' => EmployeePayrollDeduction::CALCULATION_FIXED,
            'value' => 250,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payroll.deductions.store'), [
                'user_id' => $employee->id,
                'name' => 'PhilHealth',
                'calculation_type' => EmployeePayrollDeduction::CALCULATION_FIXED,
                'value' => '100.001',
            ])
            ->assertSessionHasErrors('value');

        $this->put(route('admin.payroll.deductions.update', $deduction), [
            'name' => 'SSS',
            'calculation_type' => EmployeePayrollDeduction::CALCULATION_FIXED,
            'value' => '250.001',
        ])->assertSessionHasErrors('value');

        $this->assertSame('250.00', $deduction->fresh()->value);
        $this->assertDatabaseMissing('employee_payroll_deductions', ['name' => 'PhilHealth']);
    }

    public function test_payroll_uses_consolidated_attendance_rows(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800.00,
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-01',
            'time_in' => '2026-05-01 09:00:00',
            'time_out' => '2026-05-01 17:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payroll.index', [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertSee($employee->name)
            ->assertSee('8.00')
            ->assertSee('800.00');
    }
}
