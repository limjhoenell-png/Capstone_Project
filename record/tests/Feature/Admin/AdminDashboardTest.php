<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\PayrollSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_employee_and_distinct_logbook_account_metrics_with_latest_feeds(): void
    {
        Schema::create('logbook', function ($table): void {
            $table->increments('logbook_id');
            $table->string('full_name')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('purpose_of_transaction')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->dateTime('time_in')->nullable();
            $table->string('person_category')->nullable();
        });

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        PayrollSetting::current()->update([
            'shift_start' => '08:00:00',
            'grace_minutes' => 15,
        ]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Recent Attendance Employee',
        ]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => now()->toDateString(),
            'time_in' => now()->copy()->setTime(8, 5),
            'time_out' => now()->copy()->setTime(17, 0),
        ]);
        $lateEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Late Employee',
        ]);
        Attendance::query()->create([
            'user_id' => $lateEmployee->id,
            'attendance_date' => now()->toDateString(),
            'time_in' => now()->copy()->setTime(8, 16),
        ]);
        $onTimeEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Grace Boundary Employee',
        ]);
        Attendance::query()->create([
            'user_id' => $onTimeEmployee->id,
            'attendance_date' => now()->toDateString(),
            'time_in' => now()->copy()->setTime(8, 15),
        ]);

        DB::table('logbook')->insert([
            [
                'full_name' => 'Shared Profile',
                'contact_number' => '555-1000',
                'purpose_of_transaction' => 'Biometric visit',
                'fingerprint_id' => '42',
                'person_category' => 'visitor',
                'time_in' => '2026-11-03 09:00:00',
            ],
            [
                'full_name' => 'Shared Profile',
                'contact_number' => '555-1000',
                'purpose_of_transaction' => 'Manual visit',
                'fingerprint_id' => '0',
                'person_category' => 'visitor',
                'time_in' => '2026-11-03 10:00:00',
            ],
            [
                'full_name' => 'Resident Profile',
                'contact_number' => '555-2000',
                'purpose_of_transaction' => 'Resident transaction',
                'fingerprint_id' => null,
                'person_category' => 'resident',
                'time_in' => '2026-11-03 11:00:00',
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('background: var(--c-surface)', false)
            ->assertSee('.ops-pill--bio { background: var(--c-primary-light); color: var(--c-primary-text); }', false)
            ->assertSee('.ops-pill--manual { background: var(--c-surface-2); color: var(--c-text-secondary); }', false)
            ->assertSee('Present today')
            ->assertSee('Absent today')
            ->assertSee('Employees')
            ->assertSee('Logbook Accounts')
            ->assertSee('Late Today')
            ->assertSee('<div class="ops-kpi__value">1</div>', false)
            ->assertSee('Grace Boundary Employee')
            ->assertSee('<div class="ops-kpi__value">2</div>', false)
            ->assertSee('Attendance Latest Punches')
            ->assertSee('Recent Attendance Employee')
            ->assertSee('Logbook Latest Transaction')
            ->assertSee('Shared Profile')
            ->assertSee('Biometric')
            ->assertSee('No biometric')
            ->assertDontSee('Today&#039;s roster')
            ->assertDontSee('Latest punches')
            ->assertDontSee('>Leave</a>', false)
            ->assertDontSee('>Payroll</a>', false);
    }
}
