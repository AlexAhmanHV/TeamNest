<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_page_shows_empty_state_when_workspace_has_no_projects(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('projects.index'));

        $response->assertOk();
        $response->assertSee('No projects yet');
        $response->assertSee('Create a project to start organizing tasks.');
    }

    public function test_projects_page_hides_empty_state_when_projects_exist(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id, 'name' => 'Alpha']);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('projects.index'));

        $response->assertOk();
        $response->assertDontSee('No projects yet');
        $response->assertSee('Alpha');
    }
}
