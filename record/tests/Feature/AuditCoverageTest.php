<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\EmployeePayrollDeduction;
use App\Models\Holiday;
use App\Models\PayrollSetting;
use App\Models\Payslip;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_logbook_shows_all_shared_records_and_accounts(): void
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

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_id' => '2026-00017',
        ]);
        DB::table('logbook')->insert([
            ['full_name' => 'Mine', 'address' => 'A', 'contact_number' => '1', 'purpose_of_transaction' => 'Claim', 'fingerprint_id' => '2026-00017', 'time_in' => '2026-05-01 09:00:00'],
            ['full_name' => 'Secret Visitor', 'address' => 'Hidden', 'contact_number' => '2', 'purpose_of_transaction' => 'Private', 'fingerprint_id' => '88', 'time_in' => '2026-05-02 09:00:00'],
        ]);
        DB::table('visitor_accounts')->insert([
            'full_name' => 'Non-biometric Account',
            'address' => 'Local address',
            'contact_number' => '555-0199',
        ]);

        $this->actingAs($employee)
            ->get(route('employee.logbooks.index'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertSee('Secret Visitor')
            ->assertSee('Logbook Accounts')
            ->assertSee('Non-biometric Account')
            ->assertSee('Private');
    }

    public function test_payroll_uses_consolidated_attendance_when_present(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800,
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-04',
            'time_in' => '2026-05-04 09:00:00',
            'time_out' => '2026-05-04 17:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payroll.index', [
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertSee('800.00');

        $this->actingAs($admin)
            ->get('/admin/analytics/data?period=month')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_payroll_uses_the_consolidated_attendance_times(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_HOURLY,
            'pay_rate' => 100,
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-04',
            'time_in' => '2026-05-04 09:00:00',
            'time_out' => '2026-05-04 17:00:00',
        ]);

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-04'),
            Carbon::parse('2026-05-04')
        );

        $this->assertSame(8.0, (float) $quote['split']['total_hours']);
        $this->assertSame('attendance', $quote['split']['days'][0]['source']);
    }

    public function test_visitor_account_can_be_saved_and_listed(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.visitors.store'), [
                'full_name' => 'Ana Cruz',
                'address' => 'Purok 1',
                'contact_number' => '09170000000',
            ])
            ->assertRedirect(route('admin.visitors.index'));

        $this->assertDatabaseHas('visitor_accounts', ['full_name' => 'Ana Cruz']);

        $this->actingAs($admin)
            ->get(route('admin.visitors.index'))
            ->assertOk()
            ->assertSee('Ana Cruz');
    }

    public function test_deleting_an_employee_does_not_remove_a_different_person_with_the_same_name(): void
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

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Juan Cruz',
            'employee_id' => '15',
        ]);

        DB::table('logbook')->insert([
            ['full_name' => 'Juan Cruz', 'address' => 'A', 'contact_number' => '1', 'purpose_of_transaction' => 'Work', 'fingerprint_id' => '15', 'time_in' => now()],
            ['full_name' => 'Juan Cruz', 'address' => 'B', 'contact_number' => '2', 'purpose_of_transaction' => 'Visit', 'fingerprint_id' => '99', 'time_in' => now()],
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.employees.destroy', $employee), ['confirm_name' => 'Juan Cruz'])
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
        $this->assertDatabaseMissing('logbook', ['fingerprint_id' => '15']);
        $this->assertDatabaseHas('logbook', ['fingerprint_id' => '99']);
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
    }

    public function test_employee_sees_a_payslip_only_after_it_is_published_and_net_pay_cannot_go_negative(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 100,
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-04',
            'time_in' => '2026-05-04 09:00:00',
            'time_out' => '2026-05-04 17:00:00',
        ]);

        EmployeePayrollDeduction::query()->create([
            'user_id' => $employee->id,
            'name' => 'Cash advance',
            'calculation_type' => 'fixed_amount',
            'value' => 5000,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)->post(route('admin.payslips.process'), [
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
        ])->assertRedirect();

        $payslip = Payslip::query()->where('user_id', $employee->id)->firstOrFail();
        $this->assertSame('0.00', $payslip->net_pay);
        $this->assertSame('draft', $payslip->status);

        $this->actingAs($employee)
            ->get(route('employee.payslips.index'))
            ->assertOk()
            ->assertDontSee('100.00');

        $this->actingAs($admin)->post(route('admin.payslips.approve', $payslip))->assertRedirect();

        $this->actingAs($employee)
            ->get(route('employee.payslips.show', $payslip))
            ->assertOk()
            ->assertSee('Net Pay');
    }

    public function test_employee_can_store_a_string_device_id_and_full_name(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_EMPLOYEE,
            'employee_id' => '2026-00017',
            'department' => 'Health',
        ])->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'maria.santos@biopaylog.com',
            'employee_id' => '2026-00017',
            'full_name' => 'Maria Santos',
            'department' => 'Health',
        ]);
    }

    public function test_statutory_deductions_reduce_gross_when_enabled(): void
    {
        $settings = PayrollSetting::current();
        $settings->update(['apply_statutory' => true]);

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800,
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-04',
            'time_in' => '2026-05-04 09:00:00',
            'time_out' => '2026-05-04 17:00:00',
        ]);

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31')
        );

        $this->assertGreaterThan(0, $quote['deductions_total']);
        $this->assertLessThan(800, $quote['net_pay']);
    }

    public function test_holiday_premium_is_added_when_the_employee_worked(): void
    {
        Holiday::query()->create([
            'holiday_date' => '2026-05-01',
            'name' => 'Labor Day',
            'kind' => 'regular',
        ]);

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'pay_rate_type' => User::PAY_RATE_DAILY,
            'pay_rate' => 800,
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-05-01',
            'time_in' => '2026-05-01 09:00:00',
            'time_out' => '2026-05-01 17:00:00',
        ]);

        $quote = app(PayrollService::class)->quote(
            $employee,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-01')
        );

        $this->assertSame(800.0, (float) $quote['holiday_pay']);
        $this->assertSame(1600.0, (float) $quote['gross_pay']);
    }

    public function test_leave_requests_table_is_not_created(): void
    {
        $this->assertFalse(Schema::hasTable('leave_requests'));
    }
}
