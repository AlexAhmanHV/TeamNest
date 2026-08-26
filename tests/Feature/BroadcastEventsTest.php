<?php

namespace Tests\Feature;

use App\Events\NotificationCreated;
use App\Events\TaskCommentPosted;
use App\Events\TaskMoved;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BroadcastEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_a_task_broadcasts_task_moved(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'created_by_user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->post(route('tasks.move', $task), ['status' => 'doing'])
            ->assertRedirect();

        Event::assertDispatched(TaskMoved::class, function (TaskMoved $event) use ($task, $project, $user) {
            return $event->projectId === $project->id
                && $event->taskId === $task->id
                && $event->toStatus === 'doing'
                && $event->movedBy === $user->id;
        });
    }

    public function test_posting_a_comment_broadcasts_task_comment_posted(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by_user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->post(route('tasks.comments.store', $task), ['body' => 'Looks good to me'])
            ->assertRedirect();

        Event::assertDispatched(TaskCommentPosted::class, function (TaskCommentPosted $event) use ($task) {
            return $event->taskId === $task->id
                && $event->body === 'Looks good to me';
        });
    }

    public function test_workspace_notifier_broadcasts_notification_created(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);

        $notification = app(WorkspaceNotifier::class)->notify($user->id, $workspace, 'task.mentioned', ['task_id' => 1]);

        Event::assertDispatched(NotificationCreated::class, function (NotificationCreated $event) use ($notification, $user) {
            return $event->userId === $user->id
                && $event->notificationId === $notification->id
                && $event->type === 'task.mentioned';
        });
    }
}
