<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function log(Workspace $workspace, ?int $actorUserId, string $action, ?Model $subject = null, array $metadata = []): ActivityLog
    {
        return ActivityLog::create([
            'workspace_id' => $workspace->id,
            'actor_user_id' => $actorUserId,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }
}
