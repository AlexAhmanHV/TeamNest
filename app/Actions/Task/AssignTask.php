<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\WorkspaceNotifier;

class AssignTask
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly WorkspaceNotifier $workspaceNotifier
    ) {}

    public function execute(Task $task, User $user, ?int $assigneeId): Task
    {
        $task->update(['assigned_to_user_id' => $assigneeId]);

        $this->activityLogger->log($task->project->workspace, $user->id, 'task.assigned', $task, [
            'assigned_to_user_id' => $assigneeId,
        ]);

        if ($assigneeId && $assigneeId !== $user->id) {
            $this->workspaceNotifier->notify($assigneeId, $task->project->workspace, 'task.assigned', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'assigned_by' => $user->name,
            ]);
        }

        return $task;
    }
}
