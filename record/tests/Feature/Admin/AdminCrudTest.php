<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_employee_management_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.employees.index'))
            ->assertOk();
    }

    public function test_employee_management_supports_configurable_page_sizes_and_navigation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        for ($index = 1; $index <= 8; $index++) {
            User::factory()->create([
                'role' => User::ROLE_EMPLOYEE,
                'name' => sprintf('Pagination Employee %02d', $index),
            ]);
        }

        $firstPage = $this->actingAs($admin)
            ->get(route('admin.employees.index', ['per_page' => 5]));

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
            ->assertSee('background: var(--c-surface-2)', false)
            ->assertSee('background: var(--c-primary)', false)
            ->assertDontSee('background: #0f172a', false)
            ->assertSee('Pagination Employee 01')
            ->assertSee('Pagination Employee 05')
            ->assertDontSee('Pagination Employee 06')
            ->assertSee('per_page=5');

        $this->actingAs($admin)
            ->get(route('admin.employees.index', ['per_page' => 5, 'employees_page' => 2]))
            ->assertOk()
            ->assertSee('Pagination Employee 06')
            ->assertDontSee('Pagination Employee 01');

        $liveSync = $this->getJson(route('admin.employees.live-sync', [
            'per_page' => 5,
            'employees_page' => 2,
        ]))->assertOk()->assertJsonPath('changed', true);
        self::assertStringContainsString('Pagination Employee 06', $liveSync->json('html'));
        self::assertStringNotContainsString('Pagination Employee 01', $liveSync->json('html'));

        $this->actingAs($admin)
            ->get(route('admin.employees.index'))
            ->assertOk()
            ->assertSee('value="10" selected', false);
    }

    public function test_admin_can_create_employee_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.employees.store'), [
                'name' => 'New Employee',
                'email_username' => 'new.employee',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_EMPLOYEE,
                'position' => 'Staff',
                'contact_number' => '123456',
                'pay_rate_type' => User::PAY_RATE_DAILY,
                'pay_rate' => null,
            ])
            ->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new.employee@biopaylog.com',
            'role' => User::ROLE_EMPLOYEE,
        ]);
    }

    public function test_admin_can_update_an_employee_email_address(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'name' => 'Original Name',
            'email' => 'original.name@biopaylog.com',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.employees.update', $employee), [
                'name' => 'Updated Name',
                'email' => 'custom.address@example.com',
                'role' => User::ROLE_EMPLOYEE,
            ])
            ->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'email' => 'custom.address@example.com',
            'name' => 'Updated Name',
        ]);
    }

    public function test_employee_password_update_requires_matching_confirmation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'password' => 'original-password',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.employees.update', $employee), [
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => User::ROLE_EMPLOYEE,
                'password' => 'new-password-123',
                'password_confirmation' => 'different-password-123',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('original-password', $employee->fresh()->password));
    }
}
