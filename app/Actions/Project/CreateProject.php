<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ActivityLogger;

class CreateProject
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Workspace $workspace, User $user, array $data): Project
    {
        $project = $workspace->projects()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by_user_id' => $user->id,
        ]);

        $this->activityLogger->log($workspace, $user->id, 'project.created', $project);

        return $project;
    }
}
