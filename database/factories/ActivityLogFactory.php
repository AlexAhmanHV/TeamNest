<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\ActivityLog> */
class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'actor_user_id' => User::factory(),
            'subject_type' => null,
            'subject_id' => null,
            'action' => 'task.updated',
            'metadata' => ['sample' => true],
            'created_at' => now(),
        ];
    }
}
