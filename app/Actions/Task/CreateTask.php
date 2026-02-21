<?php

namespace App\Actions\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;

class CreateTask
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Project $project, User $user, array $data): Task
    {
        $task = $project->tasks()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'todo',
            'priority' => $data['priority'] ?? 'med',
            'due_date' => $data['due_date'] ?? null,
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'created_by_user_id' => $user->id,
        ]);

        $this->activityLogger->log($project->workspace, $user->id, 'task.created', $task);

        return $task;
    }
}
