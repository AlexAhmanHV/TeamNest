<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_matching_projects_and_tasks(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id, 'name' => 'Client Onboarding']);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by_user_id' => $user->id, 'title' => 'Client kickoff call']);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->getJson(route('search', ['q' => 'client']));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $project->id, 'name' => 'Client Onboarding']);
        $response->assertJsonFragment(['id' => $task->id, 'title' => 'Client kickoff call']);
    }

    public function test_search_returns_matching_members(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $member = User::factory()->create(['name' => 'Grace Hopper']);
        $workspace->users()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->getJson(route('search', ['q' => 'grace']));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $member->id, 'name' => 'Grace Hopper']);
    }

    public function test_search_returns_empty_arrays_for_no_matches(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->getJson(route('search', ['q' => 'zzzzz']));

        $response->assertOk();
        $response->assertExactJson(['projects' => [], 'tasks' => [], 'members' => []]);
    }

    public function test_search_requires_minimum_two_characters(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id, 'name' => 'A']);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->getJson(route('search', ['q' => 'a']));

        $response->assertOk();
        $response->assertExactJson(['projects' => [], 'tasks' => [], 'members' => []]);
    }

    public function test_search_excludes_results_from_a_different_workspace(): void
    {
        $user = User::factory()->create();
        $workspaceA = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspaceB = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspaceA->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $workspaceB->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        Project::factory()->create(['workspace_id' => $workspaceB->id, 'created_by_user_id' => $user->id, 'name' => 'Secret Project']);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspaceA->id])
            ->getJson(route('search', ['q' => 'secret']));

        $response->assertOk();
        $response->assertExactJson(['projects' => [], 'tasks' => [], 'members' => []]);
    }

    public function test_search_excludes_soft_deleted_projects(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id, 'name' => 'Deleted Project']);
        $project->delete();

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->getJson(route('search', ['q' => 'deleted']));

        $response->assertOk();
        $response->assertExactJson(['projects' => [], 'tasks' => [], 'members' => []]);
    }

    public function test_search_member_results_do_not_leak_pivot_data(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $member = User::factory()->create(['name' => 'Ada Lovelace']);
        $workspace->users()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->getJson(route('search', ['q' => 'ada']));

        $response->assertOk();
        $response->assertExactJson([
            'projects' => [],
            'tasks' => [],
            'members' => [['id' => $member->id, 'name' => 'Ada Lovelace']],
        ]);
    }
}
