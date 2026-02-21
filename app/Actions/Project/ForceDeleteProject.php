<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogger;

class ForceDeleteProject
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Project $project, User $user): void
    {
        $workspace = $project->workspace;
        $id = $project->id;

        $project->forceDelete();

        $this->activityLogger->log($workspace, $user->id, 'project.force_deleted', null, ['project_id' => $id]);
    }
}
