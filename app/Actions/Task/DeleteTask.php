<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;

class DeleteTask
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Task $task, User $user): void
    {
        $task->delete();

        $this->activityLogger->log($task->project->workspace, $user->id, 'task.deleted', $task);
    }
}
