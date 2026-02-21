<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_project_or_task_outside_current_workspace(): void
    {
        $user = User::factory()->create();

        $workspaceA = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspaceB = Workspace::factory()->create(['owner_user_id' => $user->id]);

        $workspaceA->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $workspaceB->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $projectA = Project::factory()->create(['workspace_id' => $workspaceA->id, 'created_by_user_id' => $user->id]);
        $projectB = Project::factory()->create(['workspace_id' => $workspaceB->id, 'created_by_user_id' => $user->id]);

        $taskB = Task::factory()->create(['project_id' => $projectB->id, 'created_by_user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspaceA->id])
            ->get(route('projects.show', $projectA))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspaceA->id])
            ->get(route('projects.show', $projectB))
            ->assertNotFound();

        $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspaceA->id])
            ->patch(route('tasks.update', $taskB), [
                'title' => 'X',
                'description' => null,
                'status' => 'todo',
                'priority' => 'med',
                'due_date' => null,
                'assigned_to_user_id' => null,
            ])
            ->assertNotFound();
    }
}
