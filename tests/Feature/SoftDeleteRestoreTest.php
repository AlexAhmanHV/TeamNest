<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_and_task_soft_delete_restore_and_force_delete(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by_user_id' => $user->id]);

        $session = ['current_workspace_id' => $workspace->id];

        $this->actingAs($user)->withSession($session)->delete(route('projects.destroy', $project))->assertRedirect();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);

        $this->actingAs($user)->withSession($session)->post(route('projects.restore', $project))->assertRedirect();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'deleted_at' => null]);

        $this->actingAs($user)->withSession($session)->delete(route('tasks.destroy', $task))->assertRedirect();
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);

        $this->actingAs($user)->withSession($session)->post(route('tasks.restore', $task))->assertRedirect();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);

        $this->actingAs($user)->withSession($session)->delete(route('tasks.forceDelete', $task))->assertRedirect();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
