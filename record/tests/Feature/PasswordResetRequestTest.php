<?php

namespace Tests\Feature;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_offers_a_local_password_assistance_dialog(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-reset-dialog', false)
            ->assertSee('data-open-reset-request', false)
            ->assertSee('No email will be sent.');
    }

    public function test_guest_request_creates_local_ticket_without_revealing_account_existence(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))
            ->post(route('password.email'), ['identifier' => $user->email])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'If the account can be identified, an administrator will verify the request locally.');

        $this->assertDatabaseHas('password_reset_requests', [
            'identifier' => $user->email,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->from(route('login'))
            ->post(route('password.email'), ['identifier' => 'not-a-real-account'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'If the account can be identified, an administrator will verify the request locally.');

        $this->assertDatabaseHas('password_reset_requests', [
            'identifier' => 'not-a-real-account',
            'user_id' => null,
            'status' => 'pending',
        ]);
    }

    public function test_admin_must_verify_identity_to_set_a_password_and_resolve_ticket(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['password' => 'Original-password-1!']);
        $ticket = PasswordResetRequest::query()->create([
            'user_id' => $user->id,
            'identifier' => $user->email,
        ]);
        $newPassword = 'simplepass';

        $this->actingAs($admin)
            ->get(route('admin.password-reset-requests.index'))
            ->assertOk()
            ->assertSee($user->email);

        $this->actingAs($admin)
            ->put(route('admin.password-reset-requests.resolve', $ticket), [
                'target_user_id' => $user->id,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertSessionHasErrors('identity_verified');

        $this->assertTrue(Hash::check('Original-password-1!', $user->fresh()->password));
        $this->assertSame('pending', $ticket->fresh()->status);

        $this->actingAs($admin)
            ->put(route('admin.password-reset-requests.resolve', $ticket), [
                'target_user_id' => $user->id,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
                'identity_verified' => '1',
            ])
            ->assertRedirect(route('admin.password-reset-requests.index'));

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
        $this->assertSame('resolved', $ticket->fresh()->status);
        $this->assertSame($admin->id, $ticket->fresh()->reviewed_by);
    }

    public function test_non_admin_cannot_manage_password_reset_requests(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($employee)
            ->get(route('admin.password-reset-requests.index'))
            ->assertForbidden();
    }
}
