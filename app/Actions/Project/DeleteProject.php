<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogger;

class DeleteProject
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Project $project, User $user): void
    {
        $project->delete();

        $this->activityLogger->log($project->workspace, $user->id, 'project.deleted', $project);
    }
}
