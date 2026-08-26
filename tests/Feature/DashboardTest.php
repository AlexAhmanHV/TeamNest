<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_welcome_state_when_user_has_no_workspace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Create your first workspace');
        $response->assertSee('Workspaces keep your projects, teammates, and activity isolated from other teams.');
        $response->assertDontSee('<p class="text-sm font-semibold text-white">Workspace Settings</p>', false);
    }

    public function test_dashboard_shows_select_workspace_state_when_user_has_multiple_workspaces_and_none_selected(): void
    {
        $user = User::factory()->create();
        $workspaceA = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspaceB = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspaceA->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $workspaceB->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Select a workspace');
        $response->assertDontSee('Create your first workspace');
    }

    public function test_dashboard_shows_stats_and_recent_activity_when_workspace_has_data(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);

        Task::factory()->create(['project_id' => $project->id, 'status' => 'done', 'due_date' => null, 'created_by_user_id' => $user->id]);
        Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'due_date' => now()->subDays(2)->toDateString(), 'created_by_user_id' => $user->id]);

        ActivityLog::factory()->create([
            'workspace_id' => $workspace->id,
            'actor_user_id' => $user->id,
            'action' => 'project.created',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('total', 2);
        $response->assertViewHas('done', 1);
        $response->assertViewHas('overdue', 1);
        $response->assertViewHas('completionRate', 50.0);
        $response->assertSee('project.created');
        $response->assertSee('<p class="text-sm font-semibold text-white">Workspace Settings</p>', false);
    }

    public function test_dashboard_shows_empty_activity_message_when_workspace_has_no_tasks_or_activity(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('total', 0);
        $response->assertSee('Nothing here yet.');
    }
}
