<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->workspaces()->whereKey($project->workspace_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->workspaces()->exists();
    }

    public function update(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }

    public function restore(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }
}
