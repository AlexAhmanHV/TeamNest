<?php

namespace App\Actions\Workspace;

use App\Models\User;
use App\Models\Workspace;
use App\Services\CurrentWorkspace;

class SwitchWorkspace
{
    public function __construct(private readonly CurrentWorkspace $currentWorkspace) {}

    public function execute(User $user, Workspace $workspace): void
    {
        abort_unless($user->workspaces()->whereKey($workspace->id)->exists(), 404);

        $this->currentWorkspace->set($workspace);
    }
}
