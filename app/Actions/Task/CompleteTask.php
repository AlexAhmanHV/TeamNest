<?php

namespace App\Actions\Task;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;

class CompleteTask
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Task $task, User $user): Task
    {
        $task->update(['status' => TaskStatus::Done->value]);

        $this->activityLogger->log($task->project->workspace, $user->id, 'task.completed', $task);

        return $task;
    }
}
