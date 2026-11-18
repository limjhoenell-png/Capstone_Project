<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_their_own_profile_and_password_from_sidebar_link(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Original Administrator',
            'email' => 'administrator@example.test',
            'password' => 'current-password',
        ]);
        $otherAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'other-administrator@example.test',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.profile.edit'), false);

        $profileResponse = $this->get(route('admin.profile.edit'));
        $profileResponse->assertOk()
            ->assertSee('Profile Details')
            ->assertSee('Change Password')
            ->assertSee('data-password-visibility', false);
        self::assertSame(3, substr_count($profileResponse->getContent(), 'data-password-visibility'));

        $this->put(route('admin.profile.update'), [
            'name' => 'Updated Administrator',
            'email' => 'updated-administrator@example.test',
            'contact_number' => '555-1234',
            'position' => 'System Administrator',
            'department' => 'Administration',
        ])->assertRedirect(route('admin.profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Updated Administrator',
            'full_name' => 'Updated Administrator',
            'email' => 'updated-administrator@example.test',
            'contact_number' => '555-1234',
            'position' => 'System Administrator',
            'department' => 'Administration',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $otherAdmin->id,
            'email' => 'other-administrator@example.test',
        ]);

        $this->put(route('admin.profile.password.update'), [
            'current_password' => 'incorrect-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertSessionHasErrors('current_password');

        $this->put(route('admin.profile.password.update'), [
            'current_password' => 'current-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $this->put(route('admin.profile.password.update'), [
            'current_password' => 'current-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect(route('admin.profile.edit'));

        $this->assertTrue(Hash::check('new-secure-password', $admin->fresh()->password));
    }
}
