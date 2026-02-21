<?php

namespace App\Actions\Workspace;

use App\Models\User;
use App\Models\Workspace;
use App\Services\ActivityLogger;
use Illuminate\Support\Str;

class CreateWorkspace
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(User $user, string $name): Workspace
    {
        $workspace = Workspace::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'owner_user_id' => $user->id,
        ]);

        $workspace->users()->attach($user->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $this->activityLogger->log($workspace, $user->id, 'workspace.created', $workspace);

        return $workspace;
    }
}
