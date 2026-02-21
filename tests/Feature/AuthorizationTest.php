<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_manage_invites_or_members(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_user_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);
        $workspace->users()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $invite = Invitation::factory()->create(['workspace_id' => $workspace->id, 'invited_by_user_id' => $admin->id]);

        $session = ['current_workspace_id' => $workspace->id];

        $this->actingAs($member)->withSession($session)
            ->post(route('invites.store'), ['email' => 'x@example.com', 'role' => 'member'])
            ->assertForbidden();

        $this->actingAs($member)->withSession($session)
            ->post(route('invites.resend', $invite))
            ->assertForbidden();

        $this->actingAs($member)->withSession($session)
            ->delete(route('invites.revoke', $invite))
            ->assertForbidden();

        $this->actingAs($member)->withSession($session)
            ->patch(route('members.role.update', $admin), ['role' => 'member'])
            ->assertForbidden();

        $this->actingAs($member)->withSession($session)
            ->delete(route('members.destroy', $admin))
            ->assertForbidden();
    }

    public function test_admin_can_manage_invites_and_members(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_user_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);
        $workspace->users()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $session = ['current_workspace_id' => $workspace->id];

        $this->actingAs($admin)->withSession($session)
            ->post(route('invites.store'), ['email' => 'new@example.com', 'role' => 'member'])
            ->assertRedirect();

        $this->assertDatabaseHas('invitations', ['workspace_id' => $workspace->id, 'email' => 'new@example.com']);
    }
}
