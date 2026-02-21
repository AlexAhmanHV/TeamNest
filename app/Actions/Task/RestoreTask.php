<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;

class RestoreTask
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Task $task, User $user): void
    {
        $task->restore();

        $this->activityLogger->log($task->project->workspace, $user->id, 'task.restored', $task);
    }
}
