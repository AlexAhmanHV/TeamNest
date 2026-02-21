<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Project> */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'created_by_user_id' => User::factory(),
        ];
    }
}
