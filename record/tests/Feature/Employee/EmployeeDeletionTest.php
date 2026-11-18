<?php

namespace Tests\Feature\Employee;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_delete_form_uses_a_native_method_spoofed_request(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($admin)
            ->get(route('admin.employees.index'))
            ->assertOk()
            ->assertSee('data-no-ajax', false)
            ->assertSee('select-all-employees', false)
            ->assertSee('employee-row-checkbox', false)
            ->assertSee('Delete Selected', false);

        $this->actingAs($admin)
            ->post(route('admin.employees.destroy', $employee), [
                '_method' => 'DELETE',
                'confirm_name' => $employee->name,
            ])
            ->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
    }

    public function test_employee_with_a_hyphenated_id_can_be_deleted_with_a_numeric_logbook_column(): void
    {
        Schema::create('logbook', function ($table): void {
            $table->increments('logbook_id');
            $table->unsignedInteger('fingerprint_id')->nullable();
        });

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_id' => '2025-00867',
        ]);
        DB::table('logbook')->insert(['fingerprint_id' => 2025]);

        $this->actingAs($admin)
            ->delete(route('admin.employees.destroy', $employee), [
                'confirm_name' => $employee->name,
            ])
            ->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
        $this->assertDatabaseHas('logbook', ['fingerprint_id' => 2025]);
    }

    public function test_deleting_an_employee_removes_their_daily_attendance(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_id' => '2055-9863',
        ]);
        DB::table('attendances')->insert([
            [
                'user_id' => $employee->id,
                'attendance_date' => '2026-05-01',
                'time_in' => '2026-05-01 08:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.employees.destroy', $employee), [
                'confirm_name' => $employee->name,
            ])
            ->assertRedirect(route('admin.employees.index'));
        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
        $this->assertDatabaseMissing('attendances', ['user_id' => $employee->id]);
    }

    public function test_admin_can_bulk_delete_selected_employee_accounts_and_related_attendance(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $selectedEmployees = User::factory()->count(2)->create(['role' => User::ROLE_EMPLOYEE]);
        $unselectedEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        foreach ($selectedEmployees as $employee) {
            DB::table('attendances')->insert([
                'user_id' => $employee->id,
                'attendance_date' => '2026-05-01',
                'time_in' => '2026-05-01 08:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->delete(route('admin.employees.bulk-destroy'), [
                'employee_ids' => $selectedEmployees->pluck('id')->all(),
            ])
            ->assertRedirect(route('admin.employees.index'))
            ->assertSessionHas('status', 'Deleted 2 employee accounts.');

        foreach ($selectedEmployees as $employee) {
            $this->assertDatabaseMissing('users', ['id' => $employee->id]);
            $this->assertDatabaseMissing('attendances', ['user_id' => $employee->id]);
        }
        $this->assertDatabaseHas('users', ['id' => $unselectedEmployee->id]);
    }

    public function test_bulk_delete_rejects_accounts_that_are_not_employees(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($admin)
            ->delete(route('admin.employees.bulk-destroy'), [
                'employee_ids' => [$employee->id, $admin->id],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('employee_ids');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
    }
}
