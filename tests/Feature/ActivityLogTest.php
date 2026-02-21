<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_logs_are_created_for_major_actions(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $session = ['current_workspace_id' => $workspace->id];

        $this->actingAs($user)->withSession($session)
            ->post(route('projects.store'), ['name' => 'Alpha', 'description' => 'Desc'])
            ->assertRedirect();

        $project = Project::where('workspace_id', $workspace->id)->firstOrFail();

        $this->actingAs($user)->withSession($session)
            ->post(route('tasks.store', $project), ['title' => 'Task A', 'status' => 'todo', 'priority' => 'med'])
            ->assertRedirect();

        $task = Task::where('project_id', $project->id)->firstOrFail();

        $this->actingAs($user)->withSession($session)
            ->patch(route('tasks.update', $task), [
                'title' => 'Task A updated',
                'description' => null,
                'status' => 'doing',
                'priority' => 'high',
                'due_date' => null,
                'assigned_to_user_id' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', ['workspace_id' => $workspace->id, 'action' => 'project.created']);
        $this->assertDatabaseHas('activity_logs', ['workspace_id' => $workspace->id, 'action' => 'task.created']);

        $updated = ActivityLog::where('workspace_id', $workspace->id)->where('action', 'task.updated')->latest('id')->first();
        $this->assertNotNull($updated);
        $this->assertArrayHasKey('before', $updated->metadata);
        $this->assertArrayHasKey('after', $updated->metadata);
    }
}
