<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_creation_stores_hashed_token(): void
    {
        Queue::fake();

        $admin = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);

        $this->actingAs($admin)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->post(route('invites.store'), ['email' => 'invitee@example.com', 'role' => 'member'])
            ->assertRedirect();

        $invitation = Invitation::firstOrFail();

        $this->assertNotNull($invitation->token_hash);
        $this->assertNotEquals('invitee@example.com', $invitation->token_hash);
    }

    public function test_accept_flow_attaches_user_marks_accepted_and_rejects_invalid_cases(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['email' => 'join@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        $workspace = Workspace::factory()->create(['owner_user_id' => $admin->id]);
        $workspace->users()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);

        $token = 'known-token';
        $invitation = Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'join@example.com',
            'token_hash' => hash('sha256', $token),
            'accepted_at' => null,
            'expires_at' => now()->addDay(),
            'invited_by_user_id' => $admin->id,
        ]);

        $this->actingAs($otherUser)
            ->post(route('invites.accept', ['token' => $token]))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('invites.accept', ['token' => $token]))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('workspace_user', ['workspace_id' => $workspace->id, 'user_id' => $user->id]);
        $this->assertNotNull($invitation->fresh()->accepted_at);

        $this->actingAs($user)
            ->post(route('invites.accept', ['token' => $token]))
            ->assertStatus(422);

        $expiredToken = 'expired-token';
        Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'join@example.com',
            'token_hash' => hash('sha256', $expiredToken),
            'accepted_at' => null,
            'expires_at' => now()->subDay(),
            'invited_by_user_id' => $admin->id,
        ]);

        $this->actingAs($user)
            ->post(route('invites.accept', ['token' => $expiredToken]))
            ->assertStatus(422);
    }
}
