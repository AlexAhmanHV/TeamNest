<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurrentWorkspace
{
    public function __construct(private readonly Request $request) {}

    public function id(): ?int
    {
        return $this->request->session()->get('current_workspace_id');
    }

    public function set(Workspace $workspace): void
    {
        $this->request->session()->put('current_workspace_id', $workspace->id);
    }

    public function clear(): void
    {
        $this->request->session()->forget('current_workspace_id');
    }

    public function forUser(): ?Workspace
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $workspaceId = $this->id();
        if (! $workspaceId) {
            $workspaceId = (int) ($this->request->header('X-Workspace-Id') ?: $this->request->query('workspace_id') ?: 0);
        }

        if ($workspaceId) {
            return $user->workspaces()->whereKey($workspaceId)->first();
        }

        $count = $user->workspaces()->count();

        if ($count === 1) {
            $workspace = $user->workspaces()->first();
            if ($workspace) {
                $this->set($workspace);
            }

            return $workspace;
        }

        return null;
    }

    public function requireForUser(): Workspace
    {
        $workspace = $this->forUser();

        abort_unless($workspace !== null, 404);

        return $workspace;
    }
}
