<?php

namespace Tests\Feature\Employee;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_page_includes_administrators_and_keeps_them_out_of_bulk_delete_selection(): void
    {
        $admin = User::factory()->create([
            'name' => 'Avery Administrator',
            'role' => User::ROLE_ADMIN,
        ]);
        User::factory()->create([
            'name' => 'Casey Employee',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.employees.index'))
            ->assertOk()
            ->assertSee('Avery Administrator')
            ->assertSee('Casey Employee')
            ->assertSee('<span class="badge badge-blue">admin</span>', false)
            ->assertSee('<span class="badge badge-blue">employee</span>', false);

        $this->assertMatchesRegularExpression(
            '/name="employee_ids\[\]" value="'.$admin->id.'"[^>]*disabled/',
            $response->getContent()
        );
    }

    public function test_employee_live_sync_includes_administrators_from_the_local_user_query(): void
    {
        $admin = User::factory()->create([
            'name' => 'Avery Administrator',
            'role' => User::ROLE_ADMIN,
        ]);
        User::factory()->create([
            'name' => 'Casey Employee',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.employees.live-sync'))
            ->assertOk()
            ->assertJsonPath('changed', true)
            ->assertJsonPath('perPage', 10)
            ->assertJsonPath('html', fn (string $html): bool =>
                str_contains($html, 'Avery Administrator')
                && str_contains($html, 'Casey Employee')
                && str_contains($html, '>admin</span>')
            );
    }
}
