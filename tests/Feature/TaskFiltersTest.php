<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_and_search_work(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Overdue high todo',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
            'due_date' => now()->subDay()->toDateString(),
            'assigned_to_user_id' => $user->id,
            'created_by_user_id' => $user->id,
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Done low',
            'status' => TaskStatus::Done->value,
            'priority' => TaskPriority::Low->value,
            'due_date' => now()->addDay()->toDateString(),
            'created_by_user_id' => $user->id,
        ]);

        $session = ['current_workspace_id' => $workspace->id];

        $this->actingAs($user)->withSession($session)
            ->get(route('tasks.index', $project).'?status=todo&priority=high&overdue=1&assignee=me&q=Overdue')
            ->assertOk()
            ->assertSee('Overdue high todo')
            ->assertDontSee('Done low');
    }
}
