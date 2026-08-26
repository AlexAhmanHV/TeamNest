<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Workspace;
use App\Models\WorkspaceNotification;

class WorkspaceNotifier
{
    public function notify(int $userId, Workspace $workspace, string $type, array $data = []): WorkspaceNotification
    {
        $notification = WorkspaceNotification::create([
            'workspace_id' => $workspace->id,
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
        ]);

        broadcast(new NotificationCreated($userId, $notification->id, $type))->toOthers();

        return $notification;
    }
}
