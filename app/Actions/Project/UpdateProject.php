<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogger;

class UpdateProject
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Project $project, User $user, array $data): Project
    {
        $project->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $this->activityLogger->log($project->workspace, $user->id, 'project.updated', $project);

        return $project;
    }
}
