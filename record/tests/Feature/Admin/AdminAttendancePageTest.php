<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendancePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_attendance_lists_employee_punches_stored_before_the_last_60_days(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Stored Employee',
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-04-02',
            'time_in' => '2026-04-02 08:05:00',
            'time_out' => '2026-04-02 17:10:00',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendances.index'))
            ->assertOk()
            ->assertSee('Stored Employee')
            ->assertSee('2026-04-02')
            ->assertSee('08:05 AM')
            ->assertSee('05:10 PM')
            ->assertSee('Daily Attendance')
            ->assertSee('8.00')
            ->assertSee('value="10" selected', false)
            ->assertDontSee('Raw Biometric Logs')
            ->assertDontSee('data-attendance-view');

        $html = $response->getContent();
        self::assertSame(1, substr_count($html, '<td style="font-weight:600;">Stored Employee</td>'));
        $this->assertDatabaseCount('attendances', 1);
        self::assertLessThan(strpos($html, '05:10 PM'), strpos($html, '08:05 AM'));
    }

    public function test_daily_attendance_can_be_filtered_by_date_and_reset_to_all_dates(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $firstEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Filtered Employee',
        ]);
        $secondEmployee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Other Date Employee',
        ]);

        Attendance::query()->create([
            'user_id' => $firstEmployee->id,
            'attendance_date' => '2026-04-02',
            'time_in' => '2026-04-02 08:05:00',
        ]);
        Attendance::query()->create([
            'user_id' => $secondEmployee->id,
            'attendance_date' => '2026-04-03',
            'time_in' => '2026-04-03 08:10:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendances.index', ['date' => '2026-04-02']))
            ->assertOk()
            ->assertSee('Filtered Employee')
            ->assertDontSee('Other Date Employee')
            ->assertSee('value="2026-04-02"', false)
            ->assertSee('Clear');

        $this->actingAs($admin)
            ->get(route('admin.attendances.index'))
            ->assertOk()
            ->assertSee('Filtered Employee')
            ->assertSee('Other Date Employee');
    }

    public function test_manual_time_in_validation_includes_exact_window_boundaries(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        foreach ([
            ['2026-04-04', '05:00:00', true],
            ['2026-04-05', '16:59:59', true],
            ['2026-04-06', '04:59:59', false],
            ['2026-04-07', '17:00:00', false],
        ] as [$date, $timeIn, $valid]) {
            $response = $this->actingAs($admin)->post(route('admin.attendances.store'), [
                'user_id' => $employee->id,
                'attendance_date' => $date,
                'time_in' => $date.' '.$timeIn,
            ]);

            if ($valid) {
                $response->assertRedirect(route('admin.attendances.index'));
                $this->assertTrue(Attendance::query()
                    ->where('user_id', $employee->id)
                    ->whereDate('attendance_date', $date)
                    ->exists());
            } else {
                $response->assertSessionHasErrors('time_in');
                $this->assertFalse(Attendance::query()
                    ->where('user_id', $employee->id)
                    ->whereDate('attendance_date', $date)
                    ->exists());
            }
        }
    }

    public function test_punch_allows_late_time_out_but_rejects_out_of_window_first_punch(): void
    {
            $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
            $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

            $this->actingAs($admin)
                ->post(route('admin.attendances.punch'), [
                    'user_id' => $employee->id,
                    'punched_at' => '2026-04-04 08:00:00',
                ])
                ->assertRedirect(route('admin.attendances.index'));

            $this->post(route('admin.attendances.punch'), [
                'user_id' => $employee->id,
                'punched_at' => '2026-04-04 17:36:00',
            ])->assertRedirect(route('admin.attendances.index'));

            $attendance = Attendance::query()
                ->where('user_id', $employee->id)
                ->whereDate('attendance_date', '2026-04-04')
                ->firstOrFail();
            $this->assertSame('17:36:00', $attendance->time_out->format('H:i:s'));
            $this->assertSame(9.6, (float) $attendance->total_hours);

            $outsideWindowEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
            $this->post(route('admin.attendances.punch'), [
                'user_id' => $outsideWindowEmployee->id,
                'punched_at' => '2026-04-05 17:00:00',
            ])->assertSessionHasErrors('punched_at');

            $this->assertFalse(Attendance::query()
                ->where('user_id', $outsideWindowEmployee->id)
                ->whereDate('attendance_date', '2026-04-05')
                ->exists());
    }

    public function test_invalid_legacy_time_in_is_displayed_as_absent_with_zero_hours(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Outside Window Employee',
        ]);
        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-04-02',
            'time_in' => '2026-04-02 17:00:00',
            'time_out' => '2026-04-02 23:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendances.index'))
            ->assertOk()
            ->assertSee('Outside Window Employee')
            ->assertSee('Absent')
            ->assertSee('0.00');

        $this->get(route('admin.employees.attendances', $employee))
            ->assertOk()
            ->assertSee('Absent')
            ->assertSee('0.00');

        $this->actingAs($employee)
            ->get(route('employee.attendances.index'))
            ->assertOk()
            ->assertSee('Absent')
            ->assertSee('0.00');
    }

    public function test_daily_attendance_supports_configurable_page_sizes_and_navigation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        for ($index = 1; $index <= 8; $index++) {
            $employee = User::factory()->create([
                'role' => User::ROLE_EMPLOYEE,
                'name' => sprintf('Attendance Pagination Employee %02d', $index),
            ]);
            $date = sprintf('2026-04-%02d', $index);
            Attendance::query()->create([
                'user_id' => $employee->id,
                'attendance_date' => $date,
                'time_in' => $date.' 08:00:00',
            ]);
        }

        $firstPage = $this->actingAs($admin)
            ->get(route('admin.attendances.index', ['per_page' => 5]));

        $firstPage->assertOk()
            ->assertSee('Rows per page')
            ->assertSee('value="5" selected', false)
            ->assertSee('value="10"', false)
            ->assertSee('value="15"', false)
            ->assertSee('value="20"', false)
            ->assertSee('value="25"', false)
            ->assertSee('value="50"', false)
            ->assertSee('value="75"', false)
            ->assertSee('value="100"', false)
            ->assertSee('Attendance Pagination Employee 08')
            ->assertSee('Attendance Pagination Employee 04')
            ->assertDontSee('Attendance Pagination Employee 03')
            ->assertSee('per_page=5');

        $secondPage = $this->actingAs($admin)
            ->get(route('admin.attendances.index', ['per_page' => 5, 'merged_page' => 2]));

        $secondPage->assertOk()
            ->assertSee('Attendance Pagination Employee 03')
            ->assertDontSee('Attendance Pagination Employee 08');
        self::assertSame(3, substr_count($secondPage->getContent(), '<td style="font-weight:600;">Attendance Pagination Employee'));

        $liveSync = $this->getJson(route('admin.attendances.live-sync', [
            'per_page' => 5,
            'merged_page' => 2,
        ]))->assertOk()->assertJsonPath('changed', true);
        self::assertSame(3, substr_count(
            $liveSync->json('html'),
            '<td style="font-weight:600;">Attendance Pagination Employee',
        ));
    }

    public function test_live_sync_detects_and_displays_new_biometric_punches(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Live Punch Employee',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.attendances.punch'), [
                'user_id' => $employee->id,
                'punched_at' => '2026-04-02 08:05:00',
            ])
            ->assertRedirect(route('admin.attendances.index'));

        $this->assertDatabaseCount('attendances', 1);
        $initialPayload = $this->getJson(route('admin.attendances.live-sync', ['signature' => 'initial']))
            ->assertOk()
            ->assertJsonPath('changed', true)
            ->json();

        $this->post(route('admin.attendances.punch'), [
            'user_id' => $employee->id,
            'punched_at' => '2026-04-02 17:20:00',
        ])->assertRedirect(route('admin.attendances.index'));

        $updatedResponse = $this->getJson(route('admin.attendances.live-sync', [
            'signature' => $initialPayload['signature'],
        ]))->assertOk()->assertJsonPath('changed', true);

        $html = $updatedResponse->json('html');
        self::assertStringContainsString('08:05 AM', $html);
        self::assertStringContainsString('05:20 PM', $html);
        self::assertStringContainsString('8.00', $html);
        self::assertSame(1, substr_count($html, '<td style="font-weight:600;">Live Punch Employee</td>'));
        $this->assertDatabaseCount('attendances', 1);
        $attendance = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', '2026-04-02')
            ->firstOrFail();
        $this->assertSame('9.25', $attendance->total_hours);
    }
}
