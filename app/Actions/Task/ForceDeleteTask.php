<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;

class ForceDeleteTask
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Task $task, User $user): void
    {
        $workspace = $task->project->workspace;
        $id = $task->id;

        $task->forceDelete();

        $this->activityLogger->log($workspace, $user->id, 'task.force_deleted', null, ['task_id' => $id]);
    }
}
