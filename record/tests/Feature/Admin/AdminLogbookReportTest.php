<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLogbookReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_separates_logbook_reports_and_records(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.index'))
            ->assertOk()
            ->assertSee('Open menu', false)
            ->assertSee('id="app-sidebar"', false)
            ->assertSee('Logbook Reports')
            ->assertSee('Logbook Records')
            ->assertSee('Download report')
            ->assertSee(route('admin.barangay-logbook.logs'), false)
            ->assertSee(route('admin.barangay-logbook.report', ['period' => 'day']), false);

        $records = $this->actingAs($admin)->get(route('admin.barangay-logbook.logs'));
        $records->assertOk();
        $records->assertSee('Logbook Records');
        $records->assertSee('aria-label="Breadcrumb"', false);
        $records->assertSee(route('admin.barangay-logbook.index'), false);
    }

    public function test_admin_can_download_the_logbook_report(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->get(route('admin.barangay-logbook.report', ['period' => 'nope']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('logbook-report-day-', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_employees_cannot_download_the_logbook_report(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($employee)
            ->get(route('admin.barangay-logbook.report'))
            ->assertForbidden();
    }
}
