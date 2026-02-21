<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Invitation> */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'email' => fake()->safeEmail(),
            'role' => fake()->randomElement(WorkspaceRole::values()),
            'token_hash' => hash('sha256', fake()->uuid()),
            'invited_by_user_id' => User::factory(),
            'accepted_at' => null,
            'expires_at' => now()->addDays(7),
        ];
    }
}
