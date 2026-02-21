<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogger;

class RestoreProject
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Project $project, User $user): void
    {
        $project->restore();

        $this->activityLogger->log($project->workspace, $user->id, 'project.restored', $project);
    }
}
