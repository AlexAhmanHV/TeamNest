<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $user->workspaces()->whereKey($workspace->id)->exists();
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $user->workspaces()
            ->whereKey($workspace->id)
            ->wherePivot('role', WorkspaceRole::Admin->value)
            ->exists();
    }

    public function invite(User $user, Workspace $workspace): bool
    {
        return $this->manageMembers($user, $workspace);
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_user_id === $user->id || $this->manageMembers($user, $workspace);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_user_id === $user->id || $this->manageMembers($user, $workspace);
    }
}
