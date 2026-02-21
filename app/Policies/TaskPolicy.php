<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $user->workspaces()->whereKey($task->project->workspace_id)->exists();
    }

    public function create(User $user, int $workspaceId): bool
    {
        return $user->workspaces()->whereKey($workspaceId)->exists();
    }

    public function update(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    public function restore(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }
}
