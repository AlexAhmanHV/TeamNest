<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_page_shows_empty_state_when_workspace_has_no_activity(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('activity.index'));

        $response->assertOk();
        $response->assertSee('Nothing here yet');
        $response->assertSee('Actions your team takes will show up here.');
    }

    public function test_activity_page_hides_empty_state_when_activity_exists(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        ActivityLog::factory()->create(['workspace_id' => $workspace->id, 'actor_user_id' => $user->id, 'action' => 'project.created']);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('activity.index'));

        $response->assertOk();
        $response->assertDontSee('Nothing here yet');
        $response->assertSee('project.created');
    }
}
