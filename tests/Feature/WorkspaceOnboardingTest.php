<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspaces_page_shows_empty_state_when_user_has_no_workspaces(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('workspaces.index'));

        $response->assertOk();
        $response->assertSee('No workspaces yet');
        $response->assertSee('Workspaces keep your projects, teammates, and activity isolated from other teams.');
    }

    public function test_workspaces_page_hides_empty_state_when_user_has_a_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id, 'name' => 'Acme']);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)->get(route('workspaces.index'));

        $response->assertOk();
        $response->assertDontSee('No workspaces yet');
        $response->assertSee('Acme');
    }
}
