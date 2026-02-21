<?php

namespace App\Services;

use App\Models\Workspace;
use App\Models\WorkspaceNotification;

class WorkspaceNotifier
{
    public function notify(int $userId, Workspace $workspace, string $type, array $data = []): WorkspaceNotification
    {
        return WorkspaceNotification::create([
            'workspace_id' => $workspace->id,
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
        ]);
    }
}
