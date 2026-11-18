<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use App\Services\HrisAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHrisReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_analytics_urls_redirect_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach ([
            '/admin/analytics',
            '/admin/analytics/data?period=month',
            '/admin/analytics/report?period=day',
            '/admin/hris',
            '/admin/hris/analytics?period=week',
            '/admin/hris/report?period=year',
        ] as $legacyUrl) {
            $this->actingAs($admin)
                ->get($legacyUrl)
                ->assertRedirect(route('admin.dashboard'));
        }
    }

    public function test_dashboard_attendance_summary_service_uses_the_latest_stored_punch(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $stillIn = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Clocked In',
        ]);
        $clockedOut = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Clocked Out',
        ]);
        User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'No Punch',
        ]);

        Attendance::query()->create([
            'user_id' => $stillIn->id,
            'attendance_date' => '2026-04-02',
            'time_in' => '2026-04-02 08:05:00',
        ]);
        Attendance::query()->create([
            'user_id' => $clockedOut->id,
            'attendance_date' => '2026-04-03',
            'time_in' => '2026-04-03 08:00:00',
            'time_out' => '2026-04-03 17:10:00',
        ]);

        $payload = app(HrisAnalyticsService::class)->dashboard('day');

        $monitor = collect($payload['status_monitor'])->keyBy('name');
        $records = collect($payload['recent_activity']);

        $this->assertSame('In', $monitor['Clocked In']['status']);
        $this->assertSame('Apr 2, 2026 8:05 AM', $monitor['Clocked In']['last_attendance']);
        $this->assertSame('Out', $monitor['Clocked Out']['status']);
        $this->assertSame('Apr 3, 2026 5:10 PM', $monitor['Clocked Out']['last_attendance']);
        $this->assertSame('No record', $monitor['No Punch']['status']);
        $this->assertSame('—', $monitor['No Punch']['last_attendance']);

        $open = $records->firstWhere('employee', 'Clocked In');
        $closed = $records->firstWhere('employee', 'Clocked Out');
        $this->assertNotNull($open);
        $this->assertSame('Apr 2, 2026', $open['date']);
        $this->assertSame('8:05 AM', $open['time_in']);
        $this->assertSame('—', $open['time_out']);
        $this->assertNotNull($closed);
        $this->assertSame('Apr 3, 2026', $closed['date']);
        $this->assertSame('8:00 AM', $closed['time_in']);
        $this->assertSame('5:10 PM', $closed['time_out']);
        $this->assertNull($records->firstWhere('employee', 'No Punch'));
    }
}
