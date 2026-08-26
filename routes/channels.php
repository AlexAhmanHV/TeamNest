<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('project.{projectId}', function (User $user, int $projectId) {
    $project = Project::find($projectId);

    if (! $project || ! $user->can('view', $project)) {
        return false;
    }

    return ['id' => $user->id, 'name' => $user->name];
});

Broadcast::channel('task.{taskId}', function (User $user, int $taskId) {
    $task = Task::find($taskId);

    return $task !== null && $user->can('view', $task);
});

Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});
